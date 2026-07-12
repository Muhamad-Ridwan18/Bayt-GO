import React, { useMemo, useState } from 'react';
import { Modal, ScrollView, StyleSheet, Text, View, Dimensions } from 'react-native';
import { Image } from 'expo-image';
import { FileText, X } from 'lucide-react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import AppImage from './AppImage';
import PressableScale from './PressableScale';
import {
  getImageUploadUris,
  getUploadName,
  getUploadUri,
  isImageUpload,
  isPdfUpload,
} from '../utils/uploadPreview';
import { colors, radius, spacing, typography } from '../theme/tokens';

const { width: SCREEN_W, height: SCREEN_H } = Dimensions.get('window');

function PreviewTile({ file, index, size, onRemove, onPreview }) {
  const uri = getUploadUri(file);
  const name = getUploadName(file, index);
  const image = isImageUpload(file);
  const pdf = isPdfUpload(file);

  return (
    <View style={[styles.tile, { width: size, height: size }]}>
      {image && uri ? (
        <PressableScale onPress={() => onPreview?.(uri)} haptic="light" style={styles.tilePress}>
          <AppImage uri={uri} style={styles.tileImage} rounded={radius.sm} contentFit="cover" />
        </PressableScale>
      ) : (
        <View style={styles.fileTile}>
          <FileText size={22} color={pdf ? colors.error : colors.baytgo} strokeWidth={2} />
          <Text style={styles.fileName} numberOfLines={2}>{name}</Text>
        </View>
      )}
      <PressableScale onPress={() => onRemove(index)} haptic="light" style={styles.removeBtn}>
        <X size={12} color={colors.white} strokeWidth={2.5} />
      </PressableScale>
    </View>
  );
}

export default function UploadPreviewStrip({
  files = [],
  onRemove,
  size = 76,
  style,
  empty = null,
}) {
  const [lightboxUri, setLightboxUri] = useState(null);
  const imageUris = useMemo(() => getImageUploadUris(files), [files]);

  if (!files.length) return empty;

  const openPreview = (uri) => {
    if (imageUris.includes(uri)) setLightboxUri(uri);
  };

  return (
    <>
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={[styles.strip, style]}
      >
        {files.map((file, index) => (
          <PreviewTile
            key={`${getUploadUri(file) || getUploadName(file, index)}-${index}`}
            file={file}
            index={index}
            size={size}
            onRemove={onRemove}
            onPreview={openPreview}
          />
        ))}
      </ScrollView>

      <Modal
        visible={!!lightboxUri}
        transparent
        animationType="fade"
        presentationStyle="overFullScreen"
        statusBarTranslucent
        onRequestClose={() => setLightboxUri(null)}
      >
        <View style={styles.lightboxBackdrop}>
          <PressableScale
            onPress={() => setLightboxUri(null)}
            haptic="light"
            style={styles.lightboxClose}
          >
            <X size={22} color={colors.white} strokeWidth={2} />
          </PressableScale>
          <SafeAreaView style={styles.lightboxSafe}>
            {lightboxUri ? (
              <Image
                source={{ uri: lightboxUri }}
                style={styles.lightboxImage}
                contentFit="contain"
                transition={200}
              />
            ) : null}
          </SafeAreaView>
        </View>
      </Modal>
    </>
  );
}

const styles = StyleSheet.create({
  strip: {
    gap: spacing.sm,
    paddingVertical: spacing.xs,
  },
  tile: {
    position: 'relative',
    borderRadius: radius.sm,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.surface,
  },
  tilePress: { flex: 1 },
  tileImage: { width: '100%', height: '100%' },
  fileTile: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: spacing.sm,
    gap: spacing.xs,
  },
  fileName: {
    ...typography.small,
    fontSize: 10,
    textAlign: 'center',
    color: colors.textSecondary,
  },
  removeBtn: {
    position: 'absolute',
    top: 4,
    right: 4,
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: 'rgba(15,23,42,0.72)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  lightboxBackdrop: { flex: 1, backgroundColor: 'rgba(2,6,23,0.96)' },
  lightboxSafe: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  lightboxClose: {
    position: 'absolute',
    top: spacing['5xl'],
    right: spacing.lg,
    zIndex: 2,
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  lightboxImage: { width: SCREEN_W, height: SCREEN_H * 0.72 },
});
