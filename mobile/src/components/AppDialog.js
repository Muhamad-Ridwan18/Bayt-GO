import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  Animated,
  Modal,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import {
  AlertCircle,
  AlertTriangle,
  CheckCircle2,
  HelpCircle,
  Info,
  XCircle,
} from 'lucide-react-native';
import PressableScale from '../ui/PressableScale';
import { colors, gradients, radius, shadows, spacing, typography } from '../theme/tokens';
import { registerAlertPresenter, unregisterAlertPresenter } from '../utils/alert';

const VARIANTS = {
  success: { Icon: CheckCircle2, color: colors.success, bg: colors.successLight },
  error: { Icon: XCircle, color: colors.error, bg: colors.errorLight },
  warning: { Icon: AlertCircle, color: colors.warning, bg: colors.warningLight },
  danger: { Icon: AlertTriangle, color: colors.error, bg: colors.errorLight },
  confirm: { Icon: HelpCircle, color: colors.baytgo, bg: colors.baytgoLight },
  info: { Icon: Info, color: colors.baytgo, bg: colors.baytgoLight },
};

function DialogButton({ button, onPress, stacked, primary }) {
  const isCancel = button.style === 'cancel';
  const isDestructive = button.style === 'destructive';

  if (primary && !isCancel && !isDestructive) {
    return (
      <PressableScale onPress={onPress} haptic="medium" style={[styles.btn, stacked && styles.btnStacked]}>
        <LinearGradient colors={gradients.primarySoft} style={styles.btnGradient}>
          <Text style={styles.btnPrimaryText}>{button.text}</Text>
        </LinearGradient>
      </PressableScale>
    );
  }

  return (
    <PressableScale
      onPress={onPress}
      haptic="light"
      style={[
        styles.btn,
        stacked && styles.btnStacked,
        isCancel && styles.btnCancel,
        isDestructive && styles.btnDestructive,
        !isCancel && !isDestructive && !primary && styles.btnSecondary,
      ]}
    >
      <Text
        style={[
          styles.btnText,
          isCancel && styles.btnCancelText,
          isDestructive && styles.btnDestructiveText,
          !isCancel && !isDestructive && !primary && styles.btnSecondaryText,
        ]}
      >
        {button.text}
      </Text>
    </PressableScale>
  );
}

export default function AppDialogHost() {
  const [visible, setVisible] = useState(false);
  const [payload, setPayload] = useState(null);
  const opacity = useRef(new Animated.Value(0)).current;
  const scale = useRef(new Animated.Value(0.94)).current;

  const close = useCallback((button) => {
    Animated.parallel([
      Animated.timing(opacity, { toValue: 0, duration: 160, useNativeDriver: true }),
      Animated.timing(scale, { toValue: 0.96, duration: 160, useNativeDriver: true }),
    ]).start(() => {
      setVisible(false);
      setPayload(null);
      button?.onPress?.();
    });
  }, [opacity, scale]);

  const open = useCallback((next) => {
    setPayload(next);
    setVisible(true);
    opacity.setValue(0);
    scale.setValue(0.94);
    Animated.parallel([
      Animated.spring(scale, { toValue: 1, friction: 7, tension: 90, useNativeDriver: true }),
      Animated.timing(opacity, { toValue: 1, duration: 180, useNativeDriver: true }),
    ]).start();
  }, [opacity, scale]);

  useEffect(() => {
    registerAlertPresenter(open);
    return () => unregisterAlertPresenter();
  }, [open]);

  if (!payload) return null;

  const variant = VARIANTS[payload.variant] || VARIANTS.info;
  const VariantIcon = variant.Icon;
  const isActionSheet = payload.layout === 'actions';
  const buttons = payload.buttons || [];
  const primaryIndex = buttons.findIndex((b) => b.style !== 'cancel' && b.style !== 'destructive');
  const resolvedPrimary = primaryIndex >= 0 ? primaryIndex : buttons.length - 1;

  return (
    <Modal visible={visible} transparent animationType="none" statusBarTranslucent onRequestClose={() => close()}>
      <Animated.View style={[styles.overlay, isActionSheet && styles.overlaySheet, { opacity }]}>
        <Pressable
          style={StyleSheet.absoluteFill}
          onPress={() => {
            const cancelBtn = buttons.find((b) => b.style === 'cancel');
            if (cancelBtn) close(cancelBtn);
          }}
        />
        <Animated.View
          style={[
            isActionSheet ? styles.sheet : styles.card,
            !isActionSheet && { transform: [{ scale }] },
          ]}
        >
          {!isActionSheet ? (
            <View style={styles.header}>
              <View style={[styles.iconWrap, { backgroundColor: variant.bg }]}>
                <VariantIcon size={28} color={variant.color} strokeWidth={2} />
              </View>
              {payload.title ? <Text style={styles.title}>{payload.title}</Text> : null}
              {payload.message ? <Text style={styles.message}>{payload.message}</Text> : null}
            </View>
          ) : (
            <View style={styles.sheetHeader}>
              {payload.title ? <Text style={styles.sheetTitle}>{payload.title}</Text> : null}
              {payload.message ? <Text style={styles.sheetMessage}>{payload.message}</Text> : null}
            </View>
          )}

          <View style={[styles.actions, isActionSheet && styles.actionsStack]}>
            {buttons.map((button, index) => (
              <DialogButton
                key={`${button.text}-${index}`}
                button={button}
                stacked={isActionSheet}
                primary={!isActionSheet && index === resolvedPrimary && buttons.length <= 2}
                onPress={() => close(button)}
              />
            ))}
          </View>
        </Animated.View>
      </Animated.View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: colors.overlay,
    justifyContent: 'center',
    alignItems: 'center',
    padding: spacing['2xl'],
  },
  overlaySheet: {
    justifyContent: 'flex-end',
    paddingBottom: spacing['3xl'],
  },
  card: {
    width: '100%',
    maxWidth: 360,
    backgroundColor: colors.card,
    borderRadius: radius.lg,
    padding: spacing['2xl'],
    borderWidth: 1,
    borderColor: 'rgba(226,232,240,0.8)',
    ...shadows.lg,
  },
  sheet: {
    width: '100%',
    maxWidth: 360,
    backgroundColor: colors.card,
    borderRadius: radius.lg,
    padding: spacing.lg,
    ...shadows.lg,
  },
  header: { alignItems: 'center' },
  iconWrap: {
    width: 56,
    height: 56,
    borderRadius: radius.md,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.lg,
  },
  title: {
    ...typography.subtitle,
    color: colors.textPrimary,
    textAlign: 'center',
  },
  message: {
    ...typography.caption,
    color: colors.textSecondary,
    textAlign: 'center',
    marginTop: spacing.sm,
    lineHeight: 22,
  },
  sheetHeader: {
    paddingHorizontal: spacing.md,
    paddingTop: spacing.sm,
    paddingBottom: spacing.xs,
    alignItems: 'center',
  },
  sheetTitle: {
    ...typography.body,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.textPrimary,
    textAlign: 'center',
  },
  sheetMessage: {
    ...typography.caption,
    color: colors.textSecondary,
    textAlign: 'center',
    marginTop: spacing.xs,
  },
  actions: {
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.xl,
  },
  actionsStack: {
    flexDirection: 'column',
    marginTop: spacing.lg,
  },
  btn: { flex: 1, borderRadius: radius.sm, overflow: 'hidden' },
  btnStacked: { flex: 0, width: '100%' },
  btnGradient: {
    paddingVertical: spacing.lg,
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnPrimaryText: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.white,
  },
  btnSecondary: {
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.border,
    paddingVertical: spacing.lg,
    alignItems: 'center',
  },
  btnSecondaryText: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.baytgo,
  },
  btnCancel: {
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.border,
    paddingVertical: spacing.lg,
    alignItems: 'center',
  },
  btnCancelText: {
    ...typography.caption,
    color: colors.textSecondary,
  },
  btnDestructive: {
    backgroundColor: colors.errorLight,
    borderWidth: 1,
    borderColor: '#FECACA',
    paddingVertical: spacing.lg,
    alignItems: 'center',
  },
  btnDestructiveText: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.error,
  },
  btnText: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
  },
});
