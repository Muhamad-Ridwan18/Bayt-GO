import React, { useEffect, useRef } from 'react';
import { Animated, StyleSheet, View } from 'react-native';

/**
 * Komponen Skeleton dasar dengan efek shimmer (pulse).
 * Tidak butuh library tambahan — murni Animated API.
 */
export function Skeleton({ width = '100%', height = 16, borderRadius = 10, style }) {
  const opacity = useRef(new Animated.Value(0.4)).current;

  useEffect(() => {
    const anim = Animated.loop(
      Animated.sequence([
        Animated.timing(opacity, {
          toValue: 0.9,
          duration: 700,
          useNativeDriver: true,
        }),
        Animated.timing(opacity, {
          toValue: 0.4,
          duration: 700,
          useNativeDriver: true,
        }),
      ])
    );
    anim.start();
    return () => anim.stop();
  }, []);

  return (
    <Animated.View
      style={[
        {
          width,
          height,
          borderRadius,
          backgroundColor: '#E2E8F0',
          opacity,
        },
        style,
      ]}
    />
  );
}

/** Card wrapper skeleton */
export function SkeletonCard({ children, style }) {
  return <View style={[styles.card, style]}>{children}</View>;
}

/** Baris teks skeleton */
export function SkeletonText({ width = '80%', height = 14, style }) {
  return <Skeleton width={width} height={height} borderRadius={8} style={[{ marginBottom: 8 }, style]} />;
}

/** Skeleton untuk item list (avatar + teks) */
export function SkeletonListItem({ style }) {
  return (
    <View style={[styles.listItem, style]}>
      <Skeleton width={48} height={48} borderRadius={14} />
      <View style={{ flex: 1, marginLeft: 14 }}>
        <SkeletonText width="65%" height={14} />
        <SkeletonText width="40%" height={11} style={{ marginBottom: 0 }} />
      </View>
      <Skeleton width={60} height={24} borderRadius={8} />
    </View>
  );
}

/** Skeleton untuk stat card */
export function SkeletonStatCard({ style }) {
  return (
    <SkeletonCard style={[styles.statCard, style]}>
      <Skeleton width={36} height={36} borderRadius={10} style={{ marginBottom: 10 }} />
      <SkeletonText width="60%" height={18} />
      <SkeletonText width="40%" height={11} style={{ marginBottom: 0 }} />
    </SkeletonCard>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    padding: 18,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 2,
  },
  listItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 14,
    borderBottomWidth: 1,
    borderBottomColor: '#F8FAFC',
  },
  statCard: {
    flex: 1,
  },
});
