import React, { useState } from 'react';
import { StyleSheet, TextInput, View } from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withTiming,
} from 'react-native-reanimated';
import { BlurView } from 'expo-blur';
import { Search } from 'lucide-react-native';
import { colors, motion, radius, shadows, spacing, typography } from '../theme/tokens';

export default function SearchBar({
  value,
  onChangeText,
  placeholder = 'Cari…',
  style,
  onFocus,
  onBlur,
}) {
  const [focused, setFocused] = useState(false);
  const focus = useSharedValue(0);

  const animatedStyle = useAnimatedStyle(() => ({
    transform: [{ scale: 1 + focus.value * 0.01 }],
    borderColor: focus.value > 0 ? colors.primary : colors.border,
  }));

  return (
    <Animated.View style={[styles.wrap, shadows.float, animatedStyle, style]}>
      <BlurView intensity={28} tint="light" style={styles.blur}>
        <View style={styles.inner}>
          <Search size={18} color={focused ? colors.baytgo : colors.textMuted} strokeWidth={2} />
          <TextInput
            value={value}
            onChangeText={onChangeText}
            placeholder={placeholder}
            placeholderTextColor={colors.textMuted}
            style={styles.input}
            onFocus={(e) => {
              setFocused(true);
              focus.value = withTiming(1, { duration: motion.fast });
              onFocus?.(e);
            }}
            onBlur={(e) => {
              setFocused(false);
              focus.value = withTiming(0, { duration: motion.fast });
              onBlur?.(e);
            }}
            returnKeyType="search"
          />
        </View>
      </BlurView>
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    borderRadius: radius.lg,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: 'rgba(255,255,255,0.9)',
  },
  blur: { borderRadius: radius.lg },
  inner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.xl,
    paddingVertical: spacing.lg,
  },
  input: {
    flex: 1,
    ...typography.body,
    color: colors.textPrimary,
    padding: 0,
  },
});
