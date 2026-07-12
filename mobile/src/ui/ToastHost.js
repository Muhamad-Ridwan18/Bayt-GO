import React, { useCallback, useEffect, useRef, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import Animated, { FadeInDown, FadeOutUp } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { AlertCircle, CheckCircle2, Info } from 'lucide-react-native';
import * as Haptics from 'expo-haptics';
import { registerToastPresenter, unregisterToastPresenter } from '../utils/toast';
import { colors, radius, spacing, typography } from '../theme/tokens';

const VARIANTS = {
  success: { Icon: CheckCircle2, bg: colors.successLight, border: '#BBF7D0', color: '#166534' },
  error: { Icon: AlertCircle, bg: colors.errorLight, border: '#FECACA', color: colors.error },
  info: { Icon: Info, bg: colors.baytgoLight, border: colors.border, color: colors.baytgo },
};

export default function ToastHost() {
  const insets = useSafeAreaInsets();
  const [toast, setToast] = useState(null);
  const timerRef = useRef(null);

  const hide = useCallback(() => {
    if (timerRef.current) clearTimeout(timerRef.current);
    setToast(null);
  }, []);

  const show = useCallback((payload) => {
    if (timerRef.current) clearTimeout(timerRef.current);
    setToast(payload);
    if (payload.type === 'success') {
      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success).catch(() => {});
    }
    timerRef.current = setTimeout(hide, payload.duration || 2800);
  }, [hide]);

  useEffect(() => {
    registerToastPresenter(show);
    return () => {
      unregisterToastPresenter();
      if (timerRef.current) clearTimeout(timerRef.current);
    };
  }, [show]);

  if (!toast) return null;

  const variant = VARIANTS[toast.type] || VARIANTS.info;
  const { Icon } = variant;

  return (
    <Animated.View
      entering={FadeInDown.springify().damping(18)}
      exiting={FadeOutUp.duration(180)}
      style={[styles.wrap, { top: insets.top + spacing.sm }]}
      pointerEvents="none"
    >
      <View style={[styles.toast, { backgroundColor: variant.bg, borderColor: variant.border }]}>
        <Icon size={18} color={variant.color} strokeWidth={2} />
        <Text style={[styles.text, { color: variant.color }]}>{toast.message}</Text>
      </View>
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    position: 'absolute',
    left: spacing.lg,
    right: spacing.lg,
    zIndex: 9999,
    elevation: 20,
  },
  toast: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    borderRadius: radius.md,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    shadowColor: '#0F172A',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.12,
    shadowRadius: 16,
  },
  text: {
    flex: 1,
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    lineHeight: 20,
  },
});
