import React, { useRef, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  Dimensions,
  StatusBar,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { colors } from '../theme/colors';
import { ONBOARDING_KEY, ONBOARDING_SLIDES } from '../constants/onboarding';

const { width } = Dimensions.get('window');

function Slide({ item }) {
  return (
    <View style={[styles.slide, { width }]}>
      <View style={styles.iconWrap}>
        <LinearGradient
          colors={[colors.baytgo, '#15332b']}
          style={styles.iconGradient}
        >
          <Ionicons name={item.icon} size={40} color={colors.goldLight} />
        </LinearGradient>
      </View>

      <Text style={styles.kicker}>{item.kicker}</Text>
      <Text style={styles.title}>{item.title}</Text>
      <Text style={styles.description}>{item.description}</Text>

      {item.badges && (
        <View style={styles.badgeRow}>
          {item.badges.map((badge) => (
            <View key={badge} style={styles.badge}>
              <Ionicons name="checkmark-circle" size={14} color={colors.emerald600} />
              <Text style={styles.badgeText}>{badge}</Text>
            </View>
          ))}
        </View>
      )}
    </View>
  );
}

export default function OnboardingScreen({ navigation }) {
  const listRef = useRef(null);
  const [index, setIndex] = useState(0);
  const isLast = index === ONBOARDING_SLIDES.length - 1;

  const finish = async () => {
    await AsyncStorage.setItem(ONBOARDING_KEY, '1');
    navigation.replace('Home');
  };

  const goNext = () => {
    if (isLast) {
      finish();
      return;
    }
    listRef.current?.scrollToIndex({ index: index + 1, animated: true });
  };

  const skip = () => finish();

  return (
    <View style={styles.container}>
      <StatusBar barStyle="dark-content" />
      <LinearGradient
        colors={[colors.canvas, colors.canvasSoft, colors.white]}
        style={StyleSheet.absoluteFill}
      />

      <SafeAreaView style={styles.safe} edges={['top']}>
        <View style={styles.topBar}>
          <View style={styles.logoRow}>
            <View style={styles.logoMark}>
              <Text style={styles.logoMarkText}>B</Text>
            </View>
            <Text style={styles.logoText}>BaytGo</Text>
          </View>
          {!isLast && (
            <TouchableOpacity onPress={skip} hitSlop={12}>
              <Text style={styles.skipText}>Lewati</Text>
            </TouchableOpacity>
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

          <TouchableOpacity style={styles.primaryBtn} onPress={goNext} activeOpacity={0.9}>
            <LinearGradient
              colors={[colors.baytgo, colors.baytgoDark]}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={styles.primaryGradient}
            >
              <Text style={styles.primaryText}>{isLast ? 'Mulai Sekarang' : 'Lanjut'}</Text>
              <Ionicons
                name={isLast ? 'arrow-forward' : 'chevron-forward'}
                size={20}
                color={colors.white}
              />
            </LinearGradient>
          </TouchableOpacity>
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
    paddingHorizontal: 24,
    paddingTop: 8,
    paddingBottom: 12,
  },
  logoRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  logoMark: {
    width: 36,
    height: 36,
    borderRadius: 12,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
  },
  logoMarkText: { color: colors.gold, fontSize: 18, fontWeight: '900' },
  logoText: { fontSize: 20, fontWeight: '800', color: colors.baytgo, letterSpacing: -0.5 },
  skipText: { fontSize: 14, fontWeight: '700', color: colors.slate500 },
  list: { flex: 1 },
  slide: {
    flex: 1,
    paddingHorizontal: 28,
    paddingTop: 24,
    justifyContent: 'center',
  },
  iconWrap: { marginBottom: 28 },
  iconGradient: {
    width: 88,
    height: 88,
    borderRadius: 28,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: colors.baytgo,
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.25,
    shadowRadius: 16,
    elevation: 8,
  },
  kicker: {
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 1.2,
    textTransform: 'uppercase',
    color: colors.emerald600,
    marginBottom: 12,
  },
  title: {
    fontSize: 32,
    fontWeight: '900',
    lineHeight: 38,
    color: colors.baytgo,
    letterSpacing: -0.5,
    marginBottom: 16,
  },
  description: {
    fontSize: 16,
    lineHeight: 24,
    color: colors.slate600,
    fontWeight: '500',
    maxWidth: 320,
  },
  badgeRow: { marginTop: 24, gap: 10 },
  badge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: colors.emerald50,
    alignSelf: 'flex-start',
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 999,
  },
  badgeText: { fontSize: 13, fontWeight: '700', color: colors.baytgo },
  footer: { paddingHorizontal: 24, paddingBottom: 16, gap: 20 },
  dots: { flexDirection: 'row', justifyContent: 'center', gap: 8 },
  dot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: colors.slate200,
  },
  dotActive: { width: 28, backgroundColor: colors.baytgo },
  primaryBtn: { borderRadius: 18, overflow: 'hidden' },
  primaryGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 18,
  },
  primaryText: { color: colors.white, fontSize: 16, fontWeight: '800' },
});
