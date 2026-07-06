import React from 'react';
import { View, Text, StyleSheet, Image, TouchableOpacity, Dimensions } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import { formatIdr } from '../utils/format';
import { resolveMediaUrl } from '../utils/mediaUrl';

const CARD_WIDTH = Dimensions.get('window').width * 0.62;

export default function MuthowifCard({ item, onPress }) {
  const langs = (item.languages || []).join(', ');

  return (
    <TouchableOpacity style={styles.card} onPress={onPress} activeOpacity={0.92}>
      <View style={styles.photoWrap}>
        <Image source={{ uri: resolveMediaUrl(item.avatar) }} style={styles.photo} />
        <View style={styles.verified}>
          <Ionicons name="checkmark-circle" size={16} color="#0EA5E9" />
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
        {langs ? <Text style={styles.langs} numberOfLines={2}>{langs}</Text> : null}
        <View style={styles.ratingRow}>
          <Ionicons name="star" size={14} color="#F59E0B" />
          <Text style={styles.rating}>{item.rating ?? '—'}</Text>
          <Text style={styles.reviews}>({item.reviews ?? 0})</Text>
        </View>
        <Text style={styles.price}>Mulai dari {formatIdr(item.start_price)} / hari</Text>
      </View>
    </TouchableOpacity>
  );
}

export { CARD_WIDTH };

const styles = StyleSheet.create({
  card: {
    width: CARD_WIDTH,
    marginRight: 16,
    borderRadius: 20,
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.slate100,
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.06,
    shadowRadius: 12,
    elevation: 3,
  },
  photoWrap: { aspectRatio: 4 / 5, backgroundColor: colors.slate100 },
  photo: { width: '100%', height: '100%' },
  verified: {
    position: 'absolute',
    top: 10,
    right: 10,
    backgroundColor: colors.white,
    borderRadius: 999,
    padding: 2,
  },
  body: { padding: 14 },
  name: { fontSize: 15, fontWeight: '800', color: colors.slate900 },
  locationBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    gap: 4,
    marginTop: 6,
    maxWidth: '100%',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 999,
    backgroundColor: '#E0F2FE',
    borderWidth: 1,
    borderColor: '#BAE6FD',
  },
  location: { flexShrink: 1, fontSize: 11, fontWeight: '800', color: '#0C4A6E' },
  langs: { marginTop: 4, fontSize: 11, color: colors.slate500, fontWeight: '600', lineHeight: 16 },
  ratingRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 8 },
  rating: { fontSize: 13, fontWeight: '800', color: colors.slate900 },
  reviews: { fontSize: 11, color: colors.slate500, fontWeight: '600' },
  price: { marginTop: 10, fontSize: 12, fontWeight: '800', color: colors.baytgo },
});
