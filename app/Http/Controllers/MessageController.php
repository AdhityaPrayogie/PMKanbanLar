<?php

namespace App\Http\Controllers;

use App\Support\Events;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// messages, messages/read, messages/typing (realtime via Reverb, see App\Support\Events)
class MessageController extends Controller
{
    const MESSAGE_SELECT = 'SELECT m.*, u.name as sender_name, u.avatar_url as sender_avatar FROM messages m JOIN users u ON m.sender_id = u.id';

    private static function format(object $m): array
    {
        return ['id' => $m->id, 'conversation_id' => $m->conversation_id, 'sender_id' => $m->sender_id, 'content' => $m->content,
            'image_url' => $m->image_url, 'is_read' => (bool) $m->is_read, 'created_at' => $m->created_at,
            'sender' => ['id' => $m->sender_id, 'name' => $m->sender_name, 'email' => '', 'avatar_url' => $m->sender_avatar]];
    }

    private static function conversation(string $a, string $b): string
    {
        [$one, $two] = strcmp($a, $b) < 0 ? [$a, $b] : [$b, $a];
        $sql = 'SELECT id FROM conversations WHERE user_one_id = ? AND user_two_id = ?';
        if ($r = DB::selectOne($sql, [$one, $two])) return $r->id;
        // INSERT IGNORE + re-select handles two users opening the chat at the same moment
        DB::insert('INSERT IGNORE INTO conversations (id, user_one_id, user_two_id) VALUES (?, ?, ?)', [(string) Str::uuid(), $one, $two]);
        return DB::selectOne($sql, [$one, $two])->id;
    }

    private static function markReadAndLoad(string $conv, string $uid): array
    {
        DB::update('UPDATE messages SET is_read = TRUE WHERE conversation_id = ? AND sender_id != ? AND is_read = FALSE', [$conv, $uid]);
        // read receipt goes to both participants (reader refreshes its badge, partner sees the ticks)
        $c = DB::selectOne('SELECT user_one_id, user_two_id FROM conversations WHERE id = ?', [$conv]);
        Events::broadcast('read', ['conversationId' => $conv, 'readerId' => $uid], [$c->user_one_id, $c->user_two_id]);
        return array_map([self::class, 'format'], DB::select(self::MESSAGE_SELECT.' WHERE m.conversation_id = ? ORDER BY m.created_at ASC', [$conv]));
    }

    public function index(Request $request)
    {
        $u = $request->user();
        $q = $request->query();
        $partner = ($q['with'] ?? null) ?: (array_values(array_filter([$q['userOne'] ?? null, $q['userTwo'] ?? null], fn ($id) => $id && $id !== $u->id))[0] ?? null);

        if (self::truthy($conv = $q['conversationId'] ?? null)) {
            if (! DB::selectOne('SELECT id FROM conversations WHERE id = ? AND (user_one_id = ? OR user_two_id = ?)', [$conv, $u->id, $u->id]))
                return self::err('Forbidden', 403);
            return response()->json(['messages' => self::markReadAndLoad($conv, $u->id)]);
        }
        if ($partner) {
            $conv = self::conversation($u->id, $partner);
            return response()->json(['conversationId' => $conv, 'messages' => self::markReadAndLoad($conv, $u->id)]);
        }

        $convs = array_map(fn ($c) => array_filter([
            'id' => $c->id, 'user_one_id' => $c->user_one_id, 'user_two_id' => $c->user_two_id, 'created_at' => $c->created_at,
            'other_user' => ['id' => $c->other_id, 'name' => $c->other_name, 'email' => $c->other_email, 'avatar_url' => $c->other_avatar, 'last_active_at' => $c->other_last_active],
            'last_message' => $c->lm_id ? ['id' => $c->lm_id, 'conversation_id' => $c->id, 'sender_id' => $c->lm_sender_id, 'content' => $c->lm_content,
                'image_url' => $c->lm_image_url, 'is_read' => (bool) $c->lm_is_read, 'created_at' => $c->lm_created_at] : null,
            'unread_count' => (int) $c->unread_count,
        ], fn ($v, $k) => $k !== 'last_message' || $v, ARRAY_FILTER_USE_BOTH), DB::select('SELECT c.id, c.user_one_id, c.user_two_id, c.created_at,
              u.id AS other_id, u.name AS other_name, u.email AS other_email,
              u.avatar_url AS other_avatar, u.last_active_at AS other_last_active,
              (SELECT COUNT(*) FROM messages m
                 WHERE m.conversation_id = c.id AND m.sender_id != ? AND m.is_read = FALSE) AS unread_count,
              lm.id AS lm_id, lm.sender_id AS lm_sender_id, lm.content AS lm_content,
              lm.image_url AS lm_image_url, lm.is_read AS lm_is_read, lm.created_at AS lm_created_at
       FROM conversations c
       JOIN users u ON u.id = IF(c.user_one_id = ?, c.user_two_id, c.user_one_id)
       LEFT JOIN messages lm ON lm.id = (
         SELECT m2.id FROM messages m2 WHERE m2.conversation_id = c.id ORDER BY m2.created_at DESC LIMIT 1
       )
       WHERE c.user_one_id = ? OR c.user_two_id = ?
       ORDER BY c.created_at DESC', [$u->id, $u->id, $u->id, $u->id]));

        return response()->json(['conversations' => $convs, 'totalUnreadMessages' => array_sum(array_column($convs, 'unread_count'))]);
    }

    public function store(Request $request)
    {
        $u = $request->user();
        $b = self::body();
        $img = ($b['imageUrl'] ?? null) ?: (($b['image_url'] ?? null) ?: null);
        $text = trim((string) ($b['content'] ?? ''));
        $to = $b['recipientId'] ?? null;
        if (! self::truthy($to) || $to === $u->id || ($text === '' && ! $img))
            return self::err('recipientId serta pesan teks atau gambar diperlukan', 400);
        // Conversation is always derived from sender+recipient, so a client can't post into someone else's room
        $conv = self::conversation($u->id, $to);
        if (self::truthy($b['conversationId'] ?? null) && $b['conversationId'] !== $conv)
            return self::err('conversationId tidak cocok dengan penerima', 400);
        $content = $text !== '' ? $text : '[Gambar]';
        $mid = (string) Str::uuid();
        DB::insert('INSERT INTO messages (id, conversation_id, sender_id, content, image_url, is_read) VALUES (?, ?, ?, ?, ?, FALSE)', [$mid, $conv, $u->id, $content, $img]);
        $msg = self::format(DB::selectOne(self::MESSAGE_SELECT.' WHERE m.id = ?', [$mid])) + ['recipient_id' => $to];
        Events::broadcast('message', $msg, [$u->id, $to]);
        Events::notify($to, 'Pesan baru dari '.($u->name ?: 'Rekan Tim'), $content === '[Gambar]' ? 'Mengirim lampiran gambar' : mb_substr($content, 0, 80), 'mention', $u->id);
        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function read(Request $request)
    {
        $u = $request->user();
        $conv = self::body()['conversationId'] ?? null;
        if (! self::truthy($conv)) return self::err('Missing conversationId', 400);
        // Only participants may mark a conversation as read
        $c = DB::selectOne('SELECT user_one_id, user_two_id FROM conversations WHERE id = ? AND (user_one_id = ? OR user_two_id = ?)', [$conv, $u->id, $u->id]);
        if (! $c) return self::err('Forbidden', 403);
        DB::update('UPDATE messages SET is_read = TRUE WHERE conversation_id = ? AND sender_id != ? AND is_read = FALSE', [$conv, $u->id]);
        $other = $c->user_one_id === $u->id ? $c->user_two_id : $c->user_one_id;
        DB::update("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND related_task_id = ? AND type = 'mention'", [$u->id, $other]);
        Events::broadcast('read', ['conversationId' => $conv, 'readerId' => $u->id], [$u->id, $other]);
        return response()->json(['success' => true]);
    }

    public function typing(Request $request)
    {
        $u = $request->user();
        $b = self::body();
        if (! self::truthy($b['recipientId'] ?? null)) return self::err('Missing recipientId', 400);
        Events::broadcast('typing', ['senderId' => $u->id, 'recipientId' => $b['recipientId'], 'senderName' => $u->name, 'isTyping' => self::truthy($b['isTyping'] ?? null)], [$b['recipientId']]);
        return response()->json(['success' => true]);
    }

}
