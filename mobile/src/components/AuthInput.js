import React, { useState } from 'react';
import { Platform, StyleSheet, Text, TextInput, View } from 'react-native';
import {
  Eye,
  EyeOff,
  Mail,
  Lock,
  User,
  Building2,
  CreditCard,
  Plane,
  MapPin,
  FileText,
  Gift,
  Phone,
} from 'lucide-react-native';
import PressableScale from '../ui/PressableScale';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';

const LEGACY_ICONS = {
  'mail-outline': Mail,
  'lock-closed-outline': Lock,
  'person-outline': User,
  'business-outline': Building2,
  'card-outline': CreditCard,
  'airplane-outline': Plane,
  'location-outline': MapPin,
  'document-text-outline': FileText,
  'gift-outline': Gift,
  'call-outline': Phone,
};

function resolveIcon(icon) {
  if (!icon) return null;
  if (typeof icon === 'string') return LEGACY_ICONS[icon] || null;
  return icon;
}

export default function AuthInput({
  label,
  icon,
  secureTextEntry,
  error,
  containerStyle,
  ...props
}) {
  const Icon = resolveIcon(icon);
  const [hidden, setHidden] = useState(Boolean(secureTextEntry));
  const [focused, setFocused] = useState(false);

  return (
    <View style={[styles.wrap, containerStyle]}>
      {label ? <Text style={styles.label}>{label}</Text> : null}
      <View style={[
        styles.field,
        focused && styles.fieldFocused,
        error && styles.fieldError,
      ]}>
        {Icon ? (
          <Icon size={18} color={focused ? colors.baytgo : colors.textMuted} strokeWidth={2} />
        ) : null}
        <TextInput
          style={styles.input}
          placeholderTextColor={colors.textMuted}
          secureTextEntry={hidden}
          onFocus={() => setFocused(true)}
          onBlur={() => setFocused(false)}
          {...props}
        />
        {secureTextEntry ? (
          <PressableScale onPress={() => setHidden((v) => !v)} haptic="light" scaleTo={0.9}>
            {hidden ? (
              <EyeOff size={18} color={colors.textMuted} strokeWidth={2} />
            ) : (
              <Eye size={18} color={colors.textMuted} strokeWidth={2} />
            )}
          </PressableScale>
        ) : null}
      </View>
      {error ? <Text style={styles.error}>{error}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginBottom: spacing.lg },
  label: {
    ...typography.small,
    color: colors.textSecondary,
    marginBottom: spacing.sm,
    marginLeft: spacing.xs,
  },
  field: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    backgroundColor: colors.card,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: spacing.lg,
    minHeight: layout.minTouch,
  },
  fieldFocused: {
    borderColor: colors.baytgo,
    backgroundColor: colors.card,
  },
  fieldError: { borderColor: colors.error },
  input: {
    flex: 1,
    fontSize: typography.body.fontSize,
    fontFamily: typography.body.fontFamily,
    fontWeight: typography.body.fontWeight,
    color: colors.textPrimary,
    paddingVertical: 0,
    margin: 0,
    ...(Platform.OS === 'android'
      ? { textAlignVertical: 'center', includeFontPadding: false }
      : { paddingTop: 1 }),
  },
  error: {
    ...typography.small,
    color: colors.error,
    marginTop: spacing.sm,
    marginLeft: spacing.xs,
    fontWeight: '500',
    fontFamily: 'PlusJakartaSans_500Medium',
  },
});
