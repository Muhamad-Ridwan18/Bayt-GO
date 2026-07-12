import React, { useMemo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { User } from 'lucide-react-native';
import { colors, radius, typography } from '../theme/tokens';

function initialsFromName(name) {
  if (!name) return '';
  const parts = String(name).trim().split(/\s+/).filter(Boolean);
  if (!parts.length) return '';
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
}

export default function AppImage({
  uri,
  name,
  style,
  size,
  rounded = radius.md,
  contentFit = 'cover',
  contentPosition = 'center',
}) {
  const dimensionStyle = size ? { width: size, height: size, borderRadius: rounded } : null;
  const initials = useMemo(() => initialsFromName(name), [name]);
  const fontSize = size ? Math.max(12, size * 0.34) : 14;

  if (!uri) {
    return (
      <View style={[styles.placeholder, dimensionStyle, style, { borderRadius: rounded }]}>
        {initials ? (
          <Text style={[styles.initials, { fontSize }]}>{initials}</Text>
        ) : (
          <User size={size ? size * 0.45 : 24} color={colors.textMuted} strokeWidth={1.8} />
        )}
      </View>
    );
  }

  return (
    <Image
      source={{ uri }}
      style={[dimensionStyle, style, { borderRadius: rounded }]}
      contentFit={contentFit}
      contentPosition={contentPosition}
      transition={250}
      placeholder={{ blurhash: 'L6PZfSi_.AyE_3t7t7R**0o#DgR4' }}
    />
  );
}

const styles = StyleSheet.create({
  placeholder: {
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  initials: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.baytgo,
  },
});
