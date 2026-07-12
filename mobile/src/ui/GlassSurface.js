import React from 'react';
import { StyleSheet, View } from 'react-native';
import { BlurView } from 'expo-blur';
import { colors, radius, shadows } from '../theme/tokens';

export default function GlassSurface({ children, style, intensity = 32 }) {
  return (
    <View style={[styles.wrap, shadows.md, style]}>
      <BlurView intensity={intensity} tint="light" style={styles.blur}>
        <View style={styles.inner}>{children}</View>
      </BlurView>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    borderRadius: radius.md,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.85)',
    backgroundColor: 'rgba(255,255,255,0.65)',
  },
  blur: { borderRadius: radius.md },
  inner: { backgroundColor: 'rgba(255,255,255,0.35)' },
});
