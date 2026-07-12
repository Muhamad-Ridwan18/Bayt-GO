import React, { useMemo, useState } from 'react';
import { Dimensions, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { ArrowRight, Star } from 'lucide-react-native';
import AppImage from '../../ui/AppImage';
import PressableScale from '../../ui/PressableScale';
import { colors, layout, radius, shadows, spacing, typography } from '../../theme/tokens';

const { width: SCREEN_W } = Dimensions.get('window');
const HERO_W = SCREEN_W - layout.screenPadding * 2;
const HERO_H = 168;
const SURFACE = '#1A3D34';
const BLEND_LOCATIONS = [0, 0.14, 0.28, 0.42, 0.58, 0.76];

const HERO_SLIDES = [
  {
    id: '1',
    kicker: 'Terpercaya • Transparan • Real-time',
    title: 'Temukan Muthowif Terbaik untuk Perjalanan Ibadahmu',
    cta: 'Lihat Semua Muthowif',
    image: require('../../../assets/hero/hero-muthowif.png'),
    imagePosition: 'right center',
  },
  {
    id: '2',
    kicker: 'Booking Aman • Harga Jelas',
    title: 'Pilih Muthowif Sesuai Kebutuhan dan Jadwal Ibadahmu',
    cta: 'Mulai Pencarian',
    image: require('../../../assets/hero/hero-welcome.png'),
    imagePosition: 'right center',
  },
];

const BLEND = [
  SURFACE,
  SURFACE,
  SURFACE,
  'rgba(26,61,52,0.94)',
  'rgba(26,61,52,0.58)',
  'transparent',
];

function HeroCta({ label, onPress }) {
  return (
    <PressableScale onPress={onPress} haptic="medium" style={styles.ctaPress}>
      <View style={styles.ctaBtn}>
        <Text style={styles.ctaLabel} numberOfLines={1}>{label}</Text>
        <View style={styles.ctaIcon}>
          <ArrowRight size={13} color={colors.white} strokeWidth={2.8} />
        </View>
      </View>
    </PressableScale>
  );
}

function SocialProof({ faces, countLabel, ratingLabel }) {
  return (
    <View style={styles.proofWrap}>
      <View style={styles.proofLeft}>
        <View style={styles.avatarStack}>
          {faces.map((uri, i) => (
            <View key={i} style={[styles.avatarRing, i > 0 && styles.avatarOverlap]}>
              <AppImage uri={uri} size={22} rounded={11} />
            </View>
          ))}
        </View>
        <Text style={styles.proofCount}>{countLabel}</Text>
      </View>
      <View style={styles.proofRating}>
        <Star size={11} color={colors.gold} fill={colors.gold} strokeWidth={2} />
        <Text style={styles.proofRatingText}>{ratingLabel}</Text>
      </View>
    </View>
  );
}

function HeroSlide({ slide, onCta, dotCount, activeIndex, faces, countLabel, ratingLabel }) {
  return (
    <View style={styles.card}>
      <Image
        source={slide.image}
        style={styles.image}
        contentFit="cover"
        contentPosition={slide.imagePosition}
        transition={240}
      />
      <LinearGradient
        colors={BLEND}
        locations={BLEND_LOCATIONS}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 0 }}
        style={StyleSheet.absoluteFill}
        pointerEvents="none"
      />
      <View style={styles.inner}>
        <View style={styles.kicker}>
          <Text style={styles.kickerText}>{slide.kicker}</Text>
        </View>
        <Text style={styles.title}>{slide.title}</Text>
        <SocialProof faces={faces} countLabel={countLabel} ratingLabel={ratingLabel} />
        <HeroCta label={slide.cta} onPress={onCta} />
      </View>
      <View style={styles.dots} pointerEvents="none">
        {Array.from({ length: dotCount }).map((_, i) => (
          <View key={i} style={[styles.dot, i === activeIndex && styles.dotActive]} />
        ))}
      </View>
    </View>
  );
}

export default function HeroCarousel({ onCta, faces = [], countLabel = '1.200+ Muthowif Aktif', ratingLabel = '4.9/5 (1.200 review)' }) {
  const [active, setActive] = useState(0);

  const displayFaces = useMemo(() => {
    const list = faces.filter(Boolean).slice(0, 3);
    if (list.length >= 3) return list;
    return [...list, ...Array.from({ length: 3 - list.length }, () => null)];
  }, [faces]);

  const onScroll = (e) => {
    const x = e.nativeEvent.contentOffset.x;
    const idx = Math.round(x / (HERO_W + spacing.md));
    if (idx !== active) setActive(idx);
  };

  return (
    <View style={styles.wrap}>
      <ScrollView
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        onScroll={onScroll}
        scrollEventThrottle={16}
        decelerationRate="fast"
        snapToInterval={HERO_W + spacing.md}
        contentContainerStyle={styles.scroll}
      >
        {HERO_SLIDES.map((slide) => (
          <HeroSlide
            key={slide.id}
            slide={slide}
            onCta={onCta}
            dotCount={HERO_SLIDES.length}
            activeIndex={active}
            faces={displayFaces}
            countLabel={countLabel}
            ratingLabel={ratingLabel}
          />
        ))}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginTop: spacing.md, marginBottom: spacing.xs },
  scroll: { paddingHorizontal: layout.screenPadding, gap: spacing.md },
  card: {
    width: HERO_W,
    height: HERO_H,
    borderRadius: radius.md,
    overflow: 'hidden',
    backgroundColor: SURFACE,
    ...shadows.lg,
  },
  image: { ...StyleSheet.absoluteFillObject },
  inner: {
    flex: 1,
    paddingTop: spacing.md + 2,
    paddingBottom: spacing.xl + 4,
    paddingLeft: spacing.lg,
    paddingRight: 80,
    justifyContent: 'flex-start',
    maxWidth: '74%',
  },
  kicker: {
    alignSelf: 'flex-start',
    backgroundColor: 'rgba(255,255,255,0.14)',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs + 2,
    borderRadius: radius.full,
    marginBottom: spacing.md - 1,
  },
  kickerText: {
    ...typography.label,
    fontSize: 9,
    color: 'rgba(255,255,255,0.92)',
    letterSpacing: 0.15,
  },
  title: {
    fontSize: 16,
    lineHeight: 22,
    color: colors.white,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
    letterSpacing: -0.2,
  },
  proofWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.sm + 2,
  },
  proofLeft: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm - 1 },
  avatarStack: { flexDirection: 'row', alignItems: 'center' },
  avatarRing: {
    borderWidth: 1.5,
    borderColor: SURFACE,
    borderRadius: 12,
    overflow: 'hidden',
  },
  avatarOverlap: { marginLeft: -7 },
  proofCount: {
    fontSize: 10,
    lineHeight: 13,
    fontFamily: 'PlusJakartaSans_600SemiBold',
    fontWeight: '600',
    color: 'rgba(255,255,255,0.88)',
  },
  proofRating: { flexDirection: 'row', alignItems: 'center', gap: 3 },
  proofRatingText: {
    fontSize: 10,
    lineHeight: 13,
    fontFamily: 'PlusJakartaSans_600SemiBold',
    fontWeight: '600',
    color: 'rgba(255,255,255,0.88)',
  },
  ctaPress: { marginTop: spacing.sm + 2, alignSelf: 'flex-start' },
  ctaBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    backgroundColor: colors.white,
    paddingVertical: spacing.sm + 2,
    paddingLeft: spacing.md + 2,
    paddingRight: spacing.sm + 2,
    borderRadius: radius.sm - 2,
    maxWidth: HERO_W * 0.7,
  },
  ctaLabel: {
    flexShrink: 1,
    fontSize: 11,
    lineHeight: 15,
    fontFamily: 'PlusJakartaSans_700Bold',
    fontWeight: '700',
    color: colors.baytgo,
  },
  ctaIcon: {
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
  },
  dots: {
    position: 'absolute',
    bottom: spacing.md + 2,
    left: 0,
    right: 0,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    gap: 5,
  },
  dot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: 'rgba(255,255,255,0.35)',
  },
  dotActive: {
    width: 16,
    backgroundColor: colors.primary,
  },
});
