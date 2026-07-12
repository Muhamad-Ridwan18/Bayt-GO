import React, { useRef, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  Dimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { LinearGradient } from 'expo-linear-gradient';
import {
  ArrowRight,
  Calendar,
  ChevronRight,
  CircleCheck,
  ShieldCheck,
  Users,
} from 'lucide-react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import AppLogo from '../components/AppLogo';
import { useBrand } from '../context/BrandContext';
import Button from '../ui/Button';
import PressableScale from '../ui/PressableScale';
import { colors, gradients, layout, radius, shadows, spacing, typography } from '../theme/tokens';
import { ONBOARDING_KEY, ONBOARDING_SLIDES } from '../constants/onboarding';

const { width } = Dimensions.get('window');

const SLIDE_ICONS = {
  calendar: Calendar,
  'shield-checkmark': ShieldCheck,
  people: Users,
};

function Slide({ item }) {
  const Icon = SLIDE_ICONS[item.icon] || Calendar;

  return (
    <View style={[styles.slide, { width }]}>
      <View style={styles.iconWrap}>
        <LinearGradient colors={gradients.primary} style={styles.iconGradient}>
          <Icon size={40} color={colors.goldLight} strokeWidth={1.8} />
        </LinearGradient>
      </View>

      <Text style={styles.kicker}>{item.kicker}</Text>
      <Text style={styles.title}>{item.title}</Text>
      <Text style={styles.description}>{item.description}</Text>

      {item.badges && (
        <View style={styles.badgeRow}>
          {item.badges.map((badge) => (
            <View key={badge} style={styles.badge}>
              <CircleCheck size={14} color={colors.success} strokeWidth={2.5} />
              <Text style={styles.badgeText}>{badge}</Text>
            </View>
          ))}
        </View>
      )}
    </View>
  );
}

export default function OnboardingScreen({ navigation }) {
  const { logoUrl, appName } = useBrand();
  const listRef = useRef(null);
  const [index, setIndex] = useState(0);
  const isLast = index === ONBOARDING_SLIDES.length - 1;

  const finish = async () => {
    await AsyncStorage.setItem(ONBOARDING_KEY, '1');
    navigation.replace('Main');
  };

  const goNext = () => {
    if (isLast) {
      finish();
      return;
    }
    listRef.current?.scrollToIndex({ index: index + 1, animated: true });
  };

  return (
    <View style={styles.container}>
      <StatusBar style="dark" />
      <LinearGradient
        colors={[colors.canvas, colors.canvasSoft, colors.white]}
        style={StyleSheet.absoluteFill}
      />

      <SafeAreaView style={styles.safe} edges={['top']}>
        <View style={styles.topBar}>
          <AppLogo url={logoUrl} name={appName} size={36} showName />
          {!isLast && (
            <PressableScale onPress={finish} haptic="light">
              <Text style={styles.skipText}>Lewati</Text>
            </PressableScale>
          )}
        </View>

        <FlatList
          ref={listRef}
          data={ONBOARDING_SLIDES}
          keyExtractor={(item) => item.id}
          renderItem={({ item }) => <Slide item={item} />}
          horizontal
          pagingEnabled
          showsHorizontalScrollIndicator={false}
          style={styles.list}
          onMomentumScrollEnd={(e) => {
            const next = Math.round(e.nativeEvent.contentOffset.x / width);
            setIndex(next);
          }}
          getItemLayout={(_, i) => ({ length: width, offset: width * i, index: i })}
        />

        <View style={styles.footer}>
          <View style={styles.dots}>
            {ONBOARDING_SLIDES.map((slide, i) => (
              <View
                key={slide.id}
                style={[styles.dot, i === index && styles.dotActive]}
              />
            ))}
          </View>

          <Button
            label={isLast ? 'Mulai Sekarang' : 'Lanjut'}
            onPress={goNext}
            icon={
              isLast ? (
                <ArrowRight size={20} color={colors.white} strokeWidth={2.2} />
              ) : (
                <ChevronRight size={20} color={colors.white} strokeWidth={2.2} />
              )
            }
          />
        </View>
      </SafeAreaView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  safe: { flex: 1 },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: layout.screenPadding,
    paddingTop: spacing.sm,
    paddingBottom: spacing.md,
  },
  skipText: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.textSecondary },
  list: { flex: 1 },
  slide: {
    flex: 1,
    paddingHorizontal: spacing['2xl'],
    paddingTop: spacing['2xl'],
    justifyContent: 'center',
  },
  iconWrap: { marginBottom: spacing['2xl'] },
  iconGradient: {
    width: 88,
    height: 88,
    borderRadius: radius.lg,
    alignItems: 'center',
    justifyContent: 'center',
    ...shadows.lg,
  },
  kicker: {
    ...typography.label,
    textTransform: 'uppercase',
    color: colors.success,
    marginBottom: spacing.md,
  },
  title: {
    ...typography.hero,
    color: colors.baytgo,
    letterSpacing: -0.5,
    marginBottom: spacing.lg,
  },
  description: {
    ...typography.body,
    color: colors.textSecondary,
    maxWidth: 320,
  },
  badgeRow: { marginTop: spacing['2xl'], gap: spacing.sm },
  badge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    backgroundColor: colors.successLight,
    alignSelf: 'flex-start',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
    borderRadius: radius.full,
  },
  badgeText: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.baytgo },
  footer: { paddingHorizontal: layout.screenPadding, paddingBottom: spacing.lg, gap: spacing.xl },
  dots: { flexDirection: 'row', justifyContent: 'center', gap: spacing.sm },
  dot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: colors.border,
  },
  dotActive: { width: 28, backgroundColor: colors.baytgo },
});
