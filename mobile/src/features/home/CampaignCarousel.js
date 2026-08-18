import React, { useEffect, useRef, useState } from 'react';
import { Dimensions, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import PressableScale from '../../ui/PressableScale';
import { colors, layout, radius, spacing, typography } from '../../theme/tokens';
import { resolveMediaUrl } from '../../utils/mediaUrl';

const { width: SCREEN_W } = Dimensions.get('window');
const CARD_W = SCREEN_W - layout.screenPadding * 2;
const CARD_H = 148;
const INTERVAL = CARD_W + spacing.md;

export default function CampaignCarousel({ campaigns = [], onPress }) {
  const [active, setActive] = useState(0);
  const scrollRef = useRef(null);
  const indexRef = useRef(0);

  useEffect(() => {
    if (campaigns.length < 2) return undefined;
    const id = setInterval(() => {
      const next = (indexRef.current + 1) % campaigns.length;
      indexRef.current = next;
      setActive(next);
      scrollRef.current?.scrollTo({ x: next * INTERVAL, animated: true });
    }, 5000);
    return () => clearInterval(id);
  }, [campaigns.length]);

  if (!campaigns.length) return null;

  const onScroll = (e) => {
    const idx = Math.round(e.nativeEvent.contentOffset.x / INTERVAL);
    if (idx !== active && idx >= 0 && idx < campaigns.length) {
      setActive(idx);
      indexRef.current = idx;
    }
  };

  return (
    <View style={styles.wrap}>
      <ScrollView
        ref={scrollRef}
        horizontal
        showsHorizontalScrollIndicator={false}
        onScroll={onScroll}
        scrollEventThrottle={16}
        decelerationRate="fast"
        snapToInterval={INTERVAL}
        contentContainerStyle={styles.scroll}
      >
        {campaigns.map((item) => {
          const banner = resolveMediaUrl(item.banner_url);
          return (
            <PressableScale
              key={item.slug}
              onPress={() => onPress?.(item)}
              haptic="light"
              style={styles.press}
            >
              <View style={[styles.card, { backgroundColor: item.theme_color || colors.baytgo }]}>
                {banner ? (
                  <Image source={{ uri: banner }} style={styles.image} contentFit="cover" transition={200} />
                ) : null}
                <LinearGradient
                  colors={['transparent', 'rgba(0,0,0,0.55)']}
                  style={styles.fade}
                  pointerEvents="none"
                />
                <Text style={styles.title} numberOfLines={2}>{item.title}</Text>
              </View>
            </PressableScale>
          );
        })}
      </ScrollView>
      {campaigns.length > 1 ? (
        <View style={styles.dots}>
          {campaigns.map((item, i) => (
            <View key={item.slug} style={[styles.dot, i === active && styles.dotActive]} />
          ))}
        </View>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginTop: spacing.lg },
  scroll: { paddingHorizontal: layout.screenPadding, gap: spacing.md },
  press: { width: CARD_W },
  card: {
    height: CARD_H,
    borderRadius: radius.md,
    overflow: 'hidden',
    justifyContent: 'flex-end',
    padding: spacing.lg,
  },
  image: { ...StyleSheet.absoluteFillObject },
  fade: { ...StyleSheet.absoluteFillObject },
  title: {
    ...typography.subtitle,
    fontSize: 16,
    color: colors.white,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
  },
  dots: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: spacing.xs,
    marginTop: spacing.sm,
  },
  dot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: colors.border,
  },
  dotActive: { width: 16, backgroundColor: colors.baytgo },
});
