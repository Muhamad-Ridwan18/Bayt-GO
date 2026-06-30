import React from 'react';
import { View, Text, StyleSheet, Image } from 'react-native';
import { colors } from '../theme/colors';

function formatTime(iso) {
  if (!iso) return '';
  try {
    const d = new Date(iso);
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  } catch {
    return '';
  }
}

function ChatImage({ uri, token }) {
  if (!uri) return null;
  return (
    <Image
      source={{
        uri,
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      }}
      style={styles.image}
      resizeMode="cover"
    />
  );
}

export default function ChatMessageBubble({ message, token }) {
  const isMe = message.is_me;

  return (
    <View style={[styles.row, isMe ? styles.rowMe : styles.rowOther]}>
      <View style={[styles.bubble, isMe ? styles.bubbleMe : styles.bubbleOther]}>
        {!isMe ? <Text style={styles.sender}>{message.sender_name}</Text> : null}
        {message.image_url ? <ChatImage uri={message.image_url} token={token} /> : null}
        {message.body ? (
          <Text style={[styles.body, isMe ? styles.bodyMe : styles.bodyOther]}>{message.body}</Text>
        ) : null}
        <Text style={[styles.time, isMe ? styles.timeMe : styles.timeOther]}>{formatTime(message.created_at)}</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  row: { marginBottom: 10, flexDirection: 'row' },
  rowMe: { justifyContent: 'flex-end' },
  rowOther: { justifyContent: 'flex-start' },
  bubble: { maxWidth: '82%', borderRadius: 18, padding: 12 },
  bubbleMe: { backgroundColor: colors.baytgo, borderBottomRightRadius: 4 },
  bubbleOther: { backgroundColor: colors.white, borderWidth: 1, borderColor: colors.slate100, borderBottomLeftRadius: 4 },
  sender: { fontSize: 11, fontWeight: '800', color: colors.baytgo, marginBottom: 6 },
  body: { fontSize: 14, lineHeight: 20, fontWeight: '500' },
  bodyMe: { color: colors.white },
  bodyOther: { color: colors.slate800 },
  image: { width: 220, height: 160, borderRadius: 12, marginBottom: 8, backgroundColor: colors.slate100 },
  time: { marginTop: 6, fontSize: 10, fontWeight: '600' },
  timeMe: { color: 'rgba(255,255,255,0.75)', textAlign: 'right' },
  timeOther: { color: colors.slate400 },
});
