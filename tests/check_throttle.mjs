// Login/register rate-limit check: node tests/check_throttle.mjs http://localhost:8090
import assert from 'node:assert/strict';

const BASE = process.argv[2] || 'http://localhost:8090';
const post = (path, body) => fetch(BASE + path, { method: 'POST', headers: { 'content-type': 'application/json' }, body: JSON.stringify(body) });

// A unique fake email, so a real account never gets locked
const email = `throttle-${Date.now()}@example.invalid`;
const codes = [];
for (let i = 0; i < 6; i++) codes.push((await post('/api/auth/login', { email, password: 'wrong' })).status);
console.log('login statuses:', codes.join(' '));
assert.ok(codes.slice(0, 5).every((c) => c !== 429), 'first 5 attempts must not be throttled');
const r = await post('/api/auth/login', { email, password: 'wrong' });
assert.equal(r.status, 429, '6th+ attempt must be 429');
const j = await r.json();
assert.ok(j.error?.includes('Terlalu banyak'), 'friendly {error} message');
assert.ok(r.headers.get('retry-after'), 'Retry-After header');

// Another email from the same IP is still allowed (the key is email+IP)
const other = await post('/api/auth/login', { email: `other-${Date.now()}@example.invalid`, password: 'x' });
assert.notEqual(other.status, 429, 'other email not blocked');
console.log('check_throttle: OK');
