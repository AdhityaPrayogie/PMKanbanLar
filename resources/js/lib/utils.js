// Port of lib/utils.ts. Exposed to templates as the `$u` magic (e.g. `$u.formatDate(t.due_date)`).

// clsx-lite: strings, arrays, {class: bool} objects. Use with :class when you need conditional strings.
export function cn(...inputs) {
  const out = [];
  for (const i of inputs) {
    if (!i) continue;
    if (typeof i === 'string') out.push(i);
    else if (Array.isArray(i)) out.push(cn(...i));
    else if (typeof i === 'object') for (const [k, v] of Object.entries(i)) if (v) out.push(k);
  }
  return out.join(' ');
}

export function formatDate(dateStr) {
  if (!dateStr) return '';
  try {
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  } catch {
    return '';
  }
}

export function isOverdue(dateStr) {
  if (!dateStr) return false;
  try {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return new Date(dateStr) < today;
  } catch {
    return false;
  }
}

export function formatLastActive(dateStr) {
  if (!dateStr) return { isOnline: false, text: 'Offline' };
  try {
    const diffMin = Math.floor((Date.now() - new Date(dateStr).getTime()) / 60000);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);
    if (diffMin < 3) return { isOnline: true, text: 'Sedang Online' };
    if (diffMin < 60) return { isOnline: false, text: `Aktif ${diffMin}m yang lalu` };
    if (diffHour < 24) return { isOnline: false, text: `Aktif ${diffHour}j yang lalu` };
    if (diffDay < 7) return { isOnline: false, text: `Aktif ${diffDay}h yang lalu` };
    return { isOnline: false, text: formatDate(dateStr) };
  } catch {
    return { isOnline: false, text: 'Offline' };
  }
}

// Replaces the useDebounce hook: const run = debounce(fn, 250)
export function debounce(fn, ms = 250) {
  let t;
  return (...a) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...a), ms);
  };
}
