#!/usr/bin/env node
/**
 * Kirim remote Expo push ke device/emulator (butuh Expo push token dari development build).
 *
 * Usage:
 *   node scripts/send-test-push.js --token ExponentPushToken[xxxxxx]
 *   node scripts/send-test-push.js --token ExponentPushToken[xxxxxx] --title "Halo" --body "Tes"
 */

const args = process.argv.slice(2);

function getArg(name, fallback = null) {
  const idx = args.findIndex((a) => a === `--${name}`);
  if (idx === -1) return fallback;
  return args[idx + 1] ?? fallback;
}

const token = getArg('token') || process.env.EXPO_PUSH_TOKEN;
const title = getArg('title', 'BaytGo · Remote Test');
const body = getArg('body', 'Ini push remote dari scripts/send-test-push.js');
const bookingId = getArg('booking-id', '0');

if (!token) {
  console.error('Missing --token ExponentPushToken[...]');
  process.exit(1);
}

const payload = [{
  to: token,
  title,
  body,
  sound: 'default',
  priority: 'high',
  data: {
    type: 'chat',
    booking_id: bookingId,
    booking_code: 'TEST-REMOTE',
    other_name: 'Push Test',
  },
}];

async function main() {
  const res = await fetch('https://exp.host/--/api/v2/push/send', {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Accept-Encoding': 'gzip, deflate',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  const json = await res.json().catch(() => ({}));
  console.log('HTTP', res.status);
  console.log(JSON.stringify(json, null, 2));

  const ticket = json?.data?.[0];
  if (ticket?.status === 'error') {
    console.error('Ticket error:', ticket.message, ticket.details || '');
    process.exit(2);
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
