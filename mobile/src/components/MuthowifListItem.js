import React from 'react';
import { View, Text, StyleSheet, Image, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import { formatIdr } from '../utils/format';
import { resolveMediaUrl } from '../utils/mediaUrl';

export default function MuthowifListItem({ item, onPress }) {
  const langs = (item.languages || []).join(', ');

  return (
    <TouchableOpacity style={styles.row} onPress={onPress} activeOpacity={0.92}>
      <View style={styles.avatarWrap}>
        <Image source={{ uri: resolveMediaUrl(item.avatar) }} style={styles.avatar} />
        <View style={styles.verified}>
          <Ionicons name="checkmark-circle" size={14} color="#0EA5E9" />
        </View>
      </View>
      <View style={styles.body}>
        <Text style={styles.name} numberOfLines={1}>{item.name}</Text>
        {item.location ? (
          <View style={styles.locationBadge}>
            <Ionicons name="location" size={11} color="#0369A1" />
            <Text style={styles.location} numberOfLines={1}>{item.location}</Text>
          </View>
        ) : null}
        {langs ? <Text style={styles.langs} numberOfLines={1}>{langs}</Text> : null}
        <View style={styles.metaRow}>
          <Ionicons name="star" size={13} color="#F59E0B" />
          <Text style={styles.rating}>{item.rating ?? '—'}</Text>
          <Text style={styles.reviews}>({item.reviews ?? 0})</Text>
        </View>
        <Text style={styles.price}>Mulai {formatIdr(item.start_price)} / hari</Text>
      </View>
      <Ionicons name="chevron-forward" size={18} color={colors.slate400} />
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 12,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: colors.slate100,
    gap: 12,
  },
  avatarWrap: { position: 'relative' },
  avatar: { width: 64, height: 64, borderRadius: 16, backgroundColor: colors.slate100 },
  verified: {
    position: 'absolute',
    bottom: -2,
    right: -2,
    backgroundColor: colors.white,
    borderRadius: 999,
    padding: 1,
  },
  body: { flex: 1 },
  name: { fontSize: 15, fontWeight: '800', color: colors.slate900 },
  locationBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    gap: 4,
    marginTop: 5,
    maxWidth: '100%',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 999,
    backgroundColor: '#E0F2FE',
    borderWidth: 1,
    borderColor: '#BAE6FD',
  },
  location: { flexShrink: 1, fontSize: 10, fontWeight: '800', color: '#0C4A6E' },
  langs: { marginTop: 2, fontSize: 11, color: colors.slate500, fontWeight: '600' },
  metaRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 6 },
  rating: { fontSize: 12, fontWeight: '800', color: colors.slate900 },
  reviews: { fontSize: 11, color: colors.slate500, fontWeight: '600' },
  price: { marginTop: 6, fontSize: 12, fontWeight: '800', color: colors.baytgo },
});
