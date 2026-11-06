// Port of chat/MiniChatWidget.tsx ('miniChat') and pages/Messages.tsx ('messagesPage').
// Both share one DM core: load conversation, realtime (message/read/typing), typing emit, image upload, send.
import { subscribeRealtime } from '../lib/realtime';
import { formatLastActive } from '../lib/utils';

// Object spread would freeze getters into values; copy descriptors instead.
const extend = (base, ext) => Object.defineProperties(base, Object.getOwnPropertyDescriptors(ext));

const post = (url, body) =>
  fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });

function chatCore(Alpine) {
  const kb = () => Alpine.store('kb');
  return {
    partner: null, // user object the conversation is with
    messages: [],
    conversationId: null,
    inputText: '',
    image: null, // { file, preview }
    uploading: false,
    partnerTyping: false,
    typingTimer: null,
    lastTyping: 0,
    gen: 0,
    unsub: null,
    fallback: null,

    get otherUsers() { return kb().allUsers.filter((u) => u.id !== kb().activeUser?.id); },
    time(m) { return new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); },

    // Reconnect whenever the partner changes (React effect deps)
    connect(partner) {
      this.disconnect();
      this.partner = partner;
      const me = kb().activeUser;
      if (!partner || !me) return;
      const gen = ++this.gen;

      const load = async () => {
        try {
          const data = await (await fetch(`/api/messages?with=${partner.id}`)).json();
          if (data.messages && gen === this.gen) {
            this.messages = data.messages;
            this.conversationId = data.conversationId;
          }
        } catch (err) {
          console.error('Failed to load conversation', err);
        }
      };
      load();

      this.unsub = subscribeRealtime({
        message: (m) => {
          if (m.sender_id !== partner.id && m.sender_id !== me.id) return;
          if (this.conversationId && m.conversation_id && m.conversation_id !== this.conversationId) return;
          if (!this.messages.some((x) => x.id === m.id)) this.messages = [...this.messages, m];
          // Mark incoming message as read if viewing this conversation
          if (m.sender_id === partner.id && this.canAutoRead()) {
            post('/api/messages/read', { conversationId: m.conversation_id || this.conversationId }).catch(() => {});
          }
        },
        read: ({ readerId }) => {
          if (readerId === partner.id) this.messages = this.messages.map((m) => (m.sender_id === me.id ? { ...m, is_read: true } : m));
        },
        typing: ({ senderId, isTyping }) => {
          if (senderId !== partner.id) return;
          this.partnerTyping = Boolean(isTyping);
          clearTimeout(this.typingTimer);
          if (isTyping) this.typingTimer = setTimeout(() => (this.partnerTyping = false), 3000);
        },
      });
      // Fallback sync every 30s in case of connection drop
      this.fallback = setInterval(load, 30000);
    },
    disconnect() {
      this.gen++;
      this.unsub?.();
      this.unsub = null;
      clearInterval(this.fallback);
      clearTimeout(this.typingTimer);
    },
    canAutoRead() { return true; },

    scrollEnd() { this.$nextTick(() => this.$refs.end?.scrollIntoView({ behavior: 'smooth' })); },

    selectImage(e) {
      const file = e.target.files?.[0];
      if (!file) return;
      if (!file.type.startsWith('image/')) {
        kb().toast('Hanya berkas gambar (PNG, JPG, WEBP, GIF, SVG) yang diperbolehkan di chat!', 'error');
        e.target.value = '';
        return;
      }
      this.image = { file, preview: URL.createObjectURL(file) };
    },
    clearImage() {
      this.image = null;
      if (this.$refs.file) this.$refs.file.value = '';
    },

    emitTyping(isTyping) {
      if (!kb().activeUser || !this.partner) return;
      post('/api/messages/typing', { recipientId: this.partner.id, isTyping }).catch(() => {});
    },
    onInput(val) {
      this.inputText = val;
      if (!kb().activeUser || !this.partner) return;
      if (!val.trim()) return this.emitTyping(false);
      if (Date.now() - this.lastTyping > 1500) {
        this.lastTyping = Date.now();
        this.emitTyping(true);
      }
    },

    async send() {
      const partner = this.partner;
      if ((!this.inputText.trim() && !this.image) || !kb().activeUser || !partner) return;
      const content = this.inputText.trim();
      this.inputText = '';
      this.emitTyping(false);
      try {
        let imageUrl = null;
        if (this.image) {
          this.uploading = true;
          const fd = new FormData();
          fd.append('file', this.image.file);
          const up = await fetch('/api/upload/image', { method: 'POST', body: fd });
          const upData = await up.json();
          if (!up.ok || upData.error) {
            kb().toast(upData.error || 'Gagal mengunggah gambar', 'error');
            return;
          }
          imageUrl = upData.url;
        }
        const data = await (await post('/api/messages', { conversationId: this.conversationId, recipientId: partner.id, content, imageUrl })).json();
        if (data.message) {
          if (!this.messages.some((m) => String(m.id) === String(data.message.id))) this.messages = [...this.messages, data.message];
          this.clearImage();
        }
      } catch (err) {
        console.error('Failed to send message', err);
      } finally {
        this.uploading = false;
      }
    },

    destroy() { this.disconnect(); },
  };
}

export default (Alpine) => {
  const kb = () => Alpine.store('kb');

  Alpine.data('miniChat', () => extend(chatCore(Alpine), {
    isMinimized: false,

    init() {
      this.$watch('$store.kb.isMiniChatOpen && $store.kb.miniChatUser?.id', () =>
        this.connect(kb().isMiniChatOpen ? kb().miniChatUser : null));
      this.$watch('[messages, isMinimized, partnerTyping]', () => this.scrollEnd());
      // Mark messages as read when opening or un-minimizing chat
      this.$watch('[$store.kb.isMiniChatOpen, isMinimized, conversationId]', () => {
        if (kb().isMiniChatOpen && !this.isMinimized && kb().activeUser && this.conversationId) {
          post('/api/messages/read', { conversationId: this.conversationId }).then(() => kb().fetchUnreadMessagesCount()).catch(() => {});
        }
      });
    },
    canAutoRead() { return !this.isMinimized; },
    openFab() { if (this.otherUsers.length) kb().openMiniChatWith(this.otherUsers[0]); },
  }));

  Alpine.data('messagesPage', () => extend(chatCore(Alpine), {
    contactSearch: '', // x-model.debounce.200ms
    showMobileChat: false,
    presence: null,

    init() {
      // Presence refresh. "Online" = active within 3 min and last_active_at is written at most once/min,
      // so polling faster than 60s shows nothing new. Skipped while the tab is hidden.
      this.presence = setInterval(() => { if (document.visibilityState === 'visible') kb().fetchUsers(); }, 60000);
      // Default selected contact once users load
      const pickDefault = () => { if (!this.partner && this.otherUsers.length) this.connect(this.otherUsers[0]); };
      this.$watch('otherUsers.length', pickDefault);
      pickDefault();
      this.$watch('[messages, partnerTyping]', () => this.scrollEnd());
      // Mark messages as read when viewing conversation
      this.$watch('[partner?.id, conversationId]', () => {
        if (this.partner && kb().activeUser && this.conversationId) post('/api/messages/read', { conversationId: this.conversationId }).catch(() => {});
      });
    },
    destroy() {
      clearInterval(this.presence);
      this.disconnect();
    },

    get filteredContacts() { return this.otherUsers.filter((u) => u.name.toLowerCase().includes(this.contactSearch.toLowerCase())); },
    get partnerStatus() { return this.partner ? formatLastActive(this.partner.last_active_at) : null; },
    status(u) { return formatLastActive(u.last_active_at); },
    pick(u) {
      if (this.partner?.id !== u.id) this.connect(u);
      this.showMobileChat = true;
    },
  }));
};
