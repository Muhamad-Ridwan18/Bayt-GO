import React from 'react';
import { View, Text, StyleSheet, ScrollView, Dimensions } from 'react-native';
import AppImage from '../ui/AppImage';
import { colors, spacing, radius, typography } from '../theme/tokens';
import { resolveMediaUrl } from '../utils/mediaUrl';

const IMG_W = Dimensions.get('window').width * 0.48;
const IMG_H = IMG_W * 0.65;

function GalleryRow({ images }) {
  if (!images.length) return null;

  return (
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      contentContainerStyle={styles.rowContent}
      decelerationRate="fast"
    >
      {images.map((img) => (
        <View key={img.id} style={styles.imageWrap}>
          <AppImage uri={resolveMediaUrl(img.url)} style={styles.image} rounded={radius.sm} />
        </View>
      ))}
    </ScrollView>
  );
}

export default function GallerySection({ images = [] }) {
  if (!images.length) return null;

  const mid = Math.ceil(images.length / 2);
  const row1 = images.slice(0, mid);
  const row2 = images.slice(mid);

  return (
    <View style={styles.section}>
      <View style={styles.header}>
        <Text style={styles.kicker}>Galeri Perjalanan</Text>
        <Text style={styles.title}>Momen Bersama Muthowif Kami</Text>
        <View style={styles.divider} />
      </View>
      <GalleryRow images={row1} />
      {row2.length > 0 && <GalleryRow images={row2} />}
    </View>
  );
}

const styles = StyleSheet.create({
  section: {
    marginHorizontal: -spacing.xl,
    marginBottom: spacing['2xl'],
    backgroundColor: colors.baytgoDark,
    paddingVertical: spacing['3xl'] - 4,
  },
  header: { alignItems: 'center', paddingHorizontal: spacing.xl, marginBottom: spacing.xl },
  kicker: {
    ...typography.label,
    letterSpacing: 2,
    textTransform: 'uppercase',
    color: colors.gold,
    marginBottom: spacing.sm,
  },
  title: {
    ...typography.title,
    fontSize: 22,
    fontWeight: '900',
    color: colors.white,
    textAlign: 'center',
  },
  divider: {
    marginTop: spacing.md + 2,
    width: 40,
    height: 2,
    borderRadius: 1,
    backgroundColor: colors.gold + '99',
  },
  rowContent: { paddingHorizontal: spacing.lg, gap: spacing.sm + 2, paddingBottom: spacing.sm + 2 },
  imageWrap: {
    width: IMG_W,
    height: IMG_H,
    borderRadius: radius.sm,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.12)',
  },
  image: { width: '100%', height: '100%' },
});
