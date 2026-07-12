import React, { useState } from 'react';
import { Modal, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { Expand, X } from 'lucide-react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import AppImage from './AppImage';
import PressableScale from './PressableScale';
import { colors, radius, spacing, typography } from '../theme/tokens';

export default function SingleImagePreview({
  uri,
  onRemove,
  onPress,
  size = 88,
  style,
  hint = 'Ketuk untuk perbesar',
}) {
  const [lightbox, setLightbox] = useState(false);

  if (!uri) return null;

  const openPreview = () => {
    if (onPress) {
      onPress();
      return;
    }
    setLightbox(true);
  };

  return (
    <>
      <View style={[styles.wrap, style]}>
        <PressableScale onPress={openPreview} haptic="light" style={styles.previewCard}>
          <AppImage uri={uri} size={size} rounded={radius.sm} contentFit="cover" />
          <View style={styles.expandBadge}>
            <Expand size={12} color={colors.white} strokeWidth={2.2} />
          </View>
        </PressableScale>
        <View style={styles.copy}>
          <Text style={styles.hint}>{hint}</Text>
          {onRemove ? (
            <PressableScale onPress={onRemove} haptic="light">
              <Text style={styles.removeText}>Hapus</Text>
            </PressableScale>
          ) : null}
        </View>
      </View>

      {!onPress ? (
        <Modal
          visible={lightbox}
          transparent
          animationType="fade"
          presentationStyle="overFullScreen"
          statusBarTranslucent
          onRequestClose={() => setLightbox(false)}
        >
          <View style={styles.lightboxBackdrop}>
            <SafeAreaView style={styles.lightboxSafe}>
              <PressableScale
                onPress={() => setLightbox(false)}
                haptic="light"
                style={styles.lightboxClose}
              >
                <X size={22} color={colors.white} strokeWidth={2} />
              </PressableScale>
              <Image
                source={{ uri }}
                style={styles.lightboxImage}
                contentFit="contain"
                transition={200}
              />
            </SafeAreaView>
          </View>
        </Modal>
      ) : null}
    </>
  );
}

const styles = StyleSheet.create({
  wrap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    marginBottom: spacing.sm,
  },
  previewCard: {
    position: 'relative',
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: colors.border,
    overflow: 'hidden',
  },
  expandBadge: {
    position: 'absolute',
    top: 6,
    right: 6,
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: 'rgba(15,23,42,0.55)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  copy: { flex: 1, gap: spacing.xs },
  hint: { ...typography.small, color: colors.textSecondary, fontWeight: '600' },
  removeText: { ...typography.small, color: colors.baytgo, fontWeight: '800' },
  lightboxBackdrop: { flex: 1, backgroundColor: 'rgba(2,6,23,0.96)' },
  lightboxSafe: { flex: 1, justifyContent: 'center' },
  lightboxClose: {
    position: 'absolute',
    top: spacing.lg,
    right: spacing.lg,
    zIndex: 2,
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  lightboxImage: { width: '100%', height: '72%' },
});
