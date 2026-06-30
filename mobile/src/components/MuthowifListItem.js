import React from 'react';
import { View, Text, StyleSheet, Image, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import { formatIdr } from '../utils/format';

export default function MuthowifListItem({ item, onPress }) {
  const langs = (item.languages || []).join(', ');

  return (
    <TouchableOpacity style={styles.row} onPress={onPress} activeOpacity={0.92}>
      <View style={styles.avatarWrap}>
        <Image source={{ uri: item.avatar }} style={styles.avatar} />
        <View style={styles.verified}>
          <Ionicons name="checkmark-circle" size={14} color="#0EA5E9" />
        </View>
      </View>
      <View style={styles.body}>
        <Text style={styles.name} numberOfLines={1}>{item.name}</Text>
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
  langs: { marginTop: 2, fontSize: 11, color: colors.slate500, fontWeight: '600' },
  metaRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 6 },
  rating: { fontSize: 12, fontWeight: '800', color: colors.slate900 },
  reviews: { fontSize: 11, color: colors.slate500, fontWeight: '600' },
  price: { marginTop: 6, fontSize: 12, fontWeight: '800', color: colors.baytgo },
});
