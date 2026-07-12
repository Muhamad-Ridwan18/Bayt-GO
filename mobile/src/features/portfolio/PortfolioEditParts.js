import React from 'react';
import { StyleSheet, Text, View, Dimensions } from 'react-native';
import { Image } from 'react-native';
import { Plus, Trash2, X } from 'lucide-react-native';
import AuthenticatedImage from '../../components/AuthenticatedImage';
import { Card, PressableScale } from '../../ui';
import { colors, radius, spacing, typography } from '../../theme/tokens';

const { width: SCREEN_W } = Dimensions.get('window');
export const IMG_SIZE = (SCREEN_W - 40 - 16) / 3;

export function GalleryGrid({
  visibleImages,
  newImages,
  token,
  allPreviewUrls,
  onPreview,
  onToggleDelete,
  onRemoveNew,
  onPickNew,
}) {
  return (
    <Card style={styles.galleryCard} padding={spacing.lg} elevated={false}>
      <View style={styles.galleryHead}>
        <Text style={styles.galleryTitle}>Foto dalam album</Text>
        <Text style={styles.galleryCount}>{allPreviewUrls.length} foto</Text>
      </View>

      <View style={styles.grid}>
        {visibleImages.map((img, index) => (
          <PressableScale
            key={img.id}
            onPress={() => onPreview(allPreviewUrls, index)}
            haptic="light"
            style={styles.imageWrap}
          >
            <AuthenticatedImage uri={img.url} token={token} style={styles.image} />
            <PressableScale
              onPress={() => onToggleDelete(img.id)}
              haptic="light"
              style={styles.removeBadge}
            >
              <Trash2 size={12} color={colors.white} strokeWidth={2} />
            </PressableScale>
          </PressableScale>
        ))}

        {newImages.map((img, index) => {
          const previewIndex = visibleImages.length + index;
          return (
            <PressableScale
              key={img.uri}
              onPress={() => onPreview(allPreviewUrls, previewIndex)}
              haptic="light"
              style={styles.imageWrap}
            >
              <Image source={{ uri: img.uri }} style={styles.image} />
              <View style={styles.newBadge}>
                <Text style={styles.newBadgeText}>Baru</Text>
              </View>
              <PressableScale onPress={() => onRemoveNew(index)} haptic="light" style={styles.removeBadge}>
                <X size={12} color={colors.white} strokeWidth={2.5} />
              </PressableScale>
            </PressableScale>
          );
        })}

        <PressableScale onPress={onPickNew} haptic="light" style={styles.addTile}>
          <Plus size={28} color={colors.baytgo} strokeWidth={2} />
          <Text style={styles.addTileText}>Tambah</Text>
        </PressableScale>
      </View>

      <Text style={styles.hint}>Ketuk foto untuk preview · ikon sampah untuk hapus</Text>
    </Card>
  );
}

const styles = StyleSheet.create({
  galleryCard: { marginBottom: spacing.lg },
  galleryHead: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: spacing.md },
  galleryTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  galleryCount: { ...typography.small, color: colors.textSecondary, fontWeight: '600' },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  imageWrap: {
    width: IMG_SIZE,
    height: IMG_SIZE,
    borderRadius: radius.sm,
    overflow: 'hidden',
    backgroundColor: colors.surface,
  },
  image: { width: IMG_SIZE, height: IMG_SIZE },
  removeBadge: {
    position: 'absolute',
    top: 6,
    right: 6,
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: 'rgba(185,28,28,0.9)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  newBadge: {
    position: 'absolute',
    left: 6,
    bottom: 6,
    backgroundColor: colors.baytgo,
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
  },
  newBadgeText: { fontSize: 9, fontWeight: '800', color: colors.white },
  addTile: {
    width: IMG_SIZE,
    height: IMG_SIZE,
    borderRadius: radius.sm,
    borderWidth: 1.5,
    borderColor: colors.baytgo,
    borderStyle: 'dashed',
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
  },
  addTileText: { ...typography.small, fontSize: 11, color: colors.baytgo },
  hint: { marginTop: spacing.md, ...typography.small, color: colors.textMuted, textAlign: 'center', fontWeight: '500' },
});
