import React from 'react';
import { View, Text, StyleSheet, Image, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import { formatIdr } from '../utils/format';
import { resolveMediaUrl } from '../utils/mediaUrl';

function AttrCell({ icon, label, value }) {
  return (
    <View style={styles.attrCell}>
      <Ionicons name={icon} size={16} color={colors.baytgo} />
      <Text style={styles.attrValue} numberOfLines={2}>{value || '—'}</Text>
      <Text style={styles.attrLabel}>{label}</Text>
    </View>
  );
}

export default function MuthowifListingCard({ item, onPressDetail, onPressBook }) {
  const langs = item.languages || [];
  const avatarUri = resolveMediaUrl(item.avatar);
  const rating = item.rating ?? null;
  const langTags = langs.slice(0, 2);
  const langDisplay = langs.join(', ') || '—';
  const specialty = item.specialty || 'Pendamping Ibadah';

  return (
    <View style={styles.card}>
      <View style={styles.topRow}>
        <View style={styles.avatarWrap}>
          {avatarUri ? (
            <Image source={{ uri: avatarUri }} style={styles.avatar} resizeMode="cover" />
          ) : (
            <View style={[styles.avatar, styles.avatarPlaceholder]}>
              <Ionicons name="person" size={32} color={colors.slate400} />
            </View>
          )}
          <View style={styles.verifiedDot}>
            <Ionicons name="checkmark" size={11} color={colors.white} />
          </View>
        </View>

        <View style={styles.topInfo}>
          <View style={styles.nameRow}>
            <Text style={styles.name} numberOfLines={1}>{item.name}</Text>
            <Ionicons name="heart-outline" size={20} color={colors.slate400} />
          </View>

          <View style={styles.ratingRow}>
            <Ionicons name="star" size={13} color="#F59E0B" />
            <Text style={styles.ratingText}>{rating ?? '—'}</Text>
            <Text style={styles.reviewText}>({item.reviews ?? 0} ulasan)</Text>
          </View>

          {item.experience ? (
            <Text style={styles.experienceText} numberOfLines={1}>{item.experience}</Text>
          ) : null}

          {langTags.length > 0 ? (
            <View style={styles.tagRow}>
              {langTags.map((lang) => (
                <View key={lang} style={styles.tag}>
                  <Text style={styles.tagText}>{lang}</Text>
                </View>
              ))}
            </View>
          ) : null}
        </View>
      </View>

      <View style={styles.attrGrid}>
        <AttrCell icon="person-outline" label="Spesialisasi" value={specialty} />
        <View style={styles.attrDivider} />
        <AttrCell icon="location-outline" label="Domisili" value={item.location} />
        <View style={styles.attrDivider} />
        <AttrCell icon="chatbubble-outline" label="Bahasa" value={langDisplay} />
      </View>

      {item.bio ? (
        <View style={styles.quoteBox}>
          <Ionicons name="chatbox-ellipses-outline" size={16} color={colors.baytgo} style={styles.quoteIcon} />
          <Text style={styles.quoteText}>{item.bio}</Text>
        </View>
      ) : null}

      <View style={styles.footer}>
        <View style={styles.priceBlock}>
          <Text style={styles.priceLabel}>Mulai dari</Text>
          <Text style={styles.price}>
            {formatIdr(item.start_price)}
            <Text style={styles.priceUnit}> /hari</Text>
          </Text>
        </View>

        <View style={styles.actions}>
          <TouchableOpacity style={styles.detailBtn} onPress={onPressDetail} activeOpacity={0.88}>
            <Ionicons name="information-circle-outline" size={16} color={colors.baytgo} />
            <Text style={styles.detailBtnText}>Detail</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.bookBtn} onPress={onPressBook} activeOpacity={0.88}>
            <Ionicons name="chatbubble-ellipses-outline" size={15} color={colors.white} />
            <Text style={styles.bookBtnText}>Pesan</Text>
          </TouchableOpacity>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 14,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.07,
    shadowRadius: 14,
    elevation: 4,
  },
  topRow: { flexDirection: 'row', gap: 12 },
  avatarWrap: { position: 'relative' },
  avatar: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: colors.slate100,
    borderWidth: 2,
    borderColor: colors.white,
  },
  avatarPlaceholder: { alignItems: 'center', justifyContent: 'center' },
  verifiedDot: {
    position: 'absolute',
    right: -2,
    bottom: 2,
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: colors.emerald600,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: colors.white,
  },
  topInfo: { flex: 1, paddingTop: 2 },
  nameRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  name: { flex: 1, fontSize: 16, fontWeight: '900', color: colors.slate900 },
  ratingRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 5 },
  ratingText: { fontSize: 13, fontWeight: '800', color: colors.slate900 },
  reviewText: { fontSize: 12, fontWeight: '600', color: colors.slate500 },
  experienceText: { marginTop: 4, fontSize: 12, fontWeight: '600', color: colors.slate500 },
  tagRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginTop: 8 },
  tag: {
    backgroundColor: colors.slate100,
    paddingHorizontal: 9,
    paddingVertical: 4,
    borderRadius: 999,
  },
  tagText: { fontSize: 11, fontWeight: '700', color: colors.slate600 },
  attrGrid: {
    flexDirection: 'row',
    alignItems: 'stretch',
    marginTop: 14,
    paddingTop: 14,
    borderTopWidth: 1,
    borderTopColor: colors.slate100,
  },
  attrCell: { flex: 1, alignItems: 'center', paddingHorizontal: 4 },
  attrValue: {
    marginTop: 6,
    fontSize: 11,
    fontWeight: '800',
    color: colors.slate900,
    textAlign: 'center',
    lineHeight: 15,
  },
  attrLabel: { marginTop: 3, fontSize: 10, fontWeight: '600', color: colors.slate500 },
  attrDivider: { width: 1, backgroundColor: colors.slate100, marginVertical: 4 },
  quoteBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 8,
    marginTop: 12,
    backgroundColor: colors.canvas,
    borderRadius: 12,
    padding: 12,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.06)',
  },
  quoteIcon: { marginTop: 1 },
  quoteText: { flex: 1, fontSize: 12, fontWeight: '600', color: colors.slate600, lineHeight: 18 },
  footer: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    justifyContent: 'space-between',
    marginTop: 14,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: colors.slate100,
    gap: 10,
  },
  priceBlock: { flex: 1 },
  priceLabel: { fontSize: 9, fontWeight: '700', color: colors.slate500, textTransform: 'uppercase', letterSpacing: 0.4 },
  price: { marginTop: 2, fontSize: 16, fontWeight: '900', color: colors.slate900 },
  priceUnit: { fontSize: 12, fontWeight: '700', color: colors.slate500 },
  actions: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  detailBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 12,
    backgroundColor: colors.white,
    borderWidth: 1.5,
    borderColor: colors.baytgo,
  },
  detailBtnText: { fontSize: 13, fontWeight: '800', color: colors.baytgo },
  bookBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 12,
    backgroundColor: colors.baytgo,
  },
  bookBtnText: { fontSize: 13, fontWeight: '800', color: colors.white },
});
