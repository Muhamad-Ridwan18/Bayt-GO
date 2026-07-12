import React, { useEffect, useRef } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Modal,
  Dimensions,
  FlatList,
  ActivityIndicator,
  Pressable,
} from 'react-native';
import { Image } from 'expo-image';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ChevronLeft, ChevronRight, X } from 'lucide-react-native';
import { buildChatImageSource } from '../utils/chatImageSource';
import { PressableScale } from '../ui';
import { colors, spacing, radius, typography } from '../theme/tokens';

const { width: SCREEN_W, height: SCREEN_H } = Dimensions.get('window');
const SLIDE_H = SCREEN_H * 0.72;

function LightboxSlide({ uri, token }) {
  const source = buildChatImageSource(uri, token);

  if (!source) {
    return (
      <View style={styles.slide}>
        <ActivityIndicator color={colors.white} size="large" />
      </View>
    );
  }

  return (
    <View style={styles.slide}>
      <Image
        source={source}
        style={styles.image}
        contentFit="contain"
        transition={250}
        cachePolicy="memory-disk"
      />
    </View>
  );
}

export default function ImageLightbox({
  visible,
  images,
  index,
  title,
  token,
  onClose,
  onChangeIndex,
}) {
  const listRef = useRef(null);

  useEffect(() => {
    if (!visible || !images?.length) return;
    requestAnimationFrame(() => {
      listRef.current?.scrollToIndex({ index, animated: false });
    });
  }, [visible, index, images]);

  if (!visible || !images?.length) return null;

  const goPrev = () => onChangeIndex((index - 1 + images.length) % images.length);
  const goNext = () => onChangeIndex((index + 1) % images.length);

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      presentationStyle="overFullScreen"
      statusBarTranslucent
      onRequestClose={onClose}
    >
      <View style={styles.backdrop}>
        <Pressable style={StyleSheet.absoluteFill} onPress={onClose} />
        <SafeAreaView style={styles.safe} pointerEvents="box-none">
          <View style={styles.topBar}>
            <PressableScale style={styles.iconBtn} onPress={onClose} hitSlop={8} haptic="light">
              <X size={22} color={colors.white} strokeWidth={2} />
            </PressableScale>
            <View style={styles.titleWrap}>
              <Text style={styles.title} numberOfLines={1}>{title || 'Foto'}</Text>
              {images.length > 1 ? (
                <Text style={styles.counter}>{index + 1} dari {images.length}</Text>
              ) : (
                <Text style={styles.hint}>Ketuk di luar gambar untuk tutup</Text>
              )}
            </View>
            <View style={styles.iconBtn} />
          </View>

          <FlatList
            ref={listRef}
            data={images}
            horizontal
            pagingEnabled
            showsHorizontalScrollIndicator={false}
            keyExtractor={(item, i) => `${item}-${i}`}
            initialScrollIndex={index}
            getItemLayout={(_, i) => ({ length: SCREEN_W, offset: SCREEN_W * i, index: i })}
            onMomentumScrollEnd={(event) => {
              const next = Math.round(event.nativeEvent.contentOffset.x / SCREEN_W);
              if (next !== index) onChangeIndex(next);
            }}
            renderItem={({ item }) => (
              <View style={styles.page}>
                <LightboxSlide uri={item} token={token} />
              </View>
            )}
          />

          {images.length > 1 ? (
            <View style={styles.footer}>
              <PressableScale style={styles.navBtn} onPress={goPrev} haptic="light">
                <ChevronLeft size={22} color={colors.white} strokeWidth={2} />
              </PressableScale>
              <View style={styles.dots}>
                {images.map((uri, i) => (
                  <View key={`${uri}-${i}`} style={[styles.dot, i === index && styles.dotActive]} />
                ))}
              </View>
              <PressableScale style={styles.navBtn} onPress={goNext} haptic="light">
                <ChevronRight size={22} color={colors.white} strokeWidth={2} />
              </PressableScale>
            </View>
          ) : null}
        </SafeAreaView>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: { flex: 1, backgroundColor: 'rgba(2,6,23,0.96)' },
  safe: { flex: 1 },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
    gap: spacing.sm,
    zIndex: 2,
  },
  iconBtn: {
    width: 40,
    height: 40,
    borderRadius: radius.full,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  titleWrap: { flex: 1, alignItems: 'center' },
  title: { ...typography.caption, fontWeight: '800', color: colors.white },
  counter: { marginTop: 2, ...typography.small, color: 'rgba(255,255,255,0.7)', fontWeight: '600' },
  hint: { marginTop: 2, ...typography.small, color: 'rgba(255,255,255,0.55)', fontWeight: '500' },
  page: { width: SCREEN_W, justifyContent: 'center', alignItems: 'center' },
  slide: {
    width: SCREEN_W,
    height: SLIDE_H,
    justifyContent: 'center',
    alignItems: 'center',
  },
  image: { width: SCREEN_W, height: SLIDE_H },
  footer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing['2xl'],
    paddingBottom: spacing.lg,
    paddingTop: spacing.sm,
  },
  navBtn: {
    width: 44,
    height: 44,
    borderRadius: radius.full,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  dots: { flexDirection: 'row', gap: spacing.xs, alignItems: 'center' },
  dot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: 'rgba(255,255,255,0.28)',
  },
  dotActive: { width: 18, backgroundColor: colors.white },
});
