import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  Animated,
  Modal,
  Pressable,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import { registerAlertPresenter, unregisterAlertPresenter } from '../utils/alert';

const VARIANTS = {
  success: { icon: 'checkmark-circle', color: colors.emerald600, bg: colors.emerald50 },
  error: { icon: 'close-circle', color: '#DC2626', bg: '#FEF2F2' },
  warning: { icon: 'alert-circle', color: '#D97706', bg: '#FFFBEB' },
  danger: { icon: 'warning', color: '#DC2626', bg: '#FEF2F2' },
  confirm: { icon: 'help-circle', color: colors.baytgo, bg: colors.baytgoLight },
  info: { icon: 'information-circle', color: colors.baytgo, bg: colors.baytgoLight },
};

function DialogButton({ button, onPress, stacked, primary }) {
  const isCancel = button.style === 'cancel';
  const isDestructive = button.style === 'destructive';

  if (primary && !isCancel && !isDestructive) {
    return (
      <TouchableOpacity style={[styles.btn, stacked && styles.btnStacked]} onPress={onPress} activeOpacity={0.9}>
        <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.btnGradient}>
          <Text style={styles.btnPrimaryText}>{button.text}</Text>
        </LinearGradient>
      </TouchableOpacity>
    );
  }

  return (
    <TouchableOpacity
      style={[
        styles.btn,
        stacked && styles.btnStacked,
        isCancel && styles.btnCancel,
        isDestructive && styles.btnDestructive,
        !isCancel && !isDestructive && !primary && styles.btnSecondary,
      ]}
      onPress={onPress}
      activeOpacity={0.85}
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
    </TouchableOpacity>
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
                <Ionicons name={variant.icon} size={28} color={variant.color} />
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
    backgroundColor: 'rgba(15, 23, 42, 0.45)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  overlaySheet: {
    justifyContent: 'flex-end',
    paddingBottom: 28,
  },
  card: {
    width: '100%',
    maxWidth: 360,
    backgroundColor: colors.white,
    borderRadius: 24,
    padding: 22,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 16 },
    shadowOpacity: 0.12,
    shadowRadius: 24,
    elevation: 10,
  },
  sheet: {
    width: '100%',
    maxWidth: 360,
    backgroundColor: colors.white,
    borderRadius: 24,
    padding: 12,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
  },
  header: { alignItems: 'center' },
  iconWrap: {
    width: 56,
    height: 56,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 14,
  },
  title: {
    fontSize: 18,
    fontWeight: '900',
    color: colors.slate900,
    textAlign: 'center',
  },
  message: {
    marginTop: 8,
    fontSize: 14,
    lineHeight: 20,
    fontWeight: '500',
    color: colors.slate600,
    textAlign: 'center',
  },
  sheetHeader: {
    paddingHorizontal: 10,
    paddingTop: 8,
    paddingBottom: 4,
    alignItems: 'center',
  },
  sheetTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: colors.slate900,
    textAlign: 'center',
  },
  sheetMessage: {
    marginTop: 4,
    fontSize: 13,
    fontWeight: '500',
    color: colors.slate500,
    textAlign: 'center',
  },
  actions: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 20,
  },
  actionsStack: {
    flexDirection: 'column',
    marginTop: 12,
  },
  btn: { flex: 1, borderRadius: 14, overflow: 'hidden' },
  btnStacked: { flex: 0, width: '100%' },
  btnGradient: {
    paddingVertical: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnPrimaryText: { fontSize: 15, fontWeight: '800', color: colors.white },
  btnSecondary: {
    backgroundColor: colors.canvas,
    borderWidth: 1,
    borderColor: colors.slate200,
    paddingVertical: 14,
    alignItems: 'center',
  },
  btnSecondaryText: { fontSize: 15, fontWeight: '800', color: colors.baytgo },
  btnCancel: {
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.slate200,
    paddingVertical: 14,
    alignItems: 'center',
  },
  btnCancelText: { fontSize: 15, fontWeight: '700', color: colors.slate500 },
  btnDestructive: {
    backgroundColor: '#FEF2F2',
    borderWidth: 1,
    borderColor: '#FECACA',
    paddingVertical: 14,
    alignItems: 'center',
  },
  btnDestructiveText: { fontSize: 15, fontWeight: '800', color: '#DC2626' },
  btnText: { fontSize: 15, fontWeight: '800' },
});
