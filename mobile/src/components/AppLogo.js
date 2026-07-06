import React from 'react';
import { View, Text, StyleSheet, Image } from 'react-native';
import { resolveMediaUrl } from '../utils/mediaUrl';
import { colors } from '../theme/colors';

const FALLBACK_LOGO = require('../../assets/logo.png');

export default function AppLogo({ url, name = 'BaytGo', size = 36, showName = false, nameStyle, variant = 'default' }) {
  const source = url ? { uri: resolveMediaUrl(url) } : FALLBACK_LOGO;
  const isLight = variant === 'light';

  return (
    <View style={styles.row}>
      <Image
        source={source}
        style={{ width: size, height: size, borderRadius: Math.round(size * 0.28) }}
        resizeMode="contain"
      />
      {showName ? (
        <Text
          style={[styles.name, isLight && styles.nameLight, nameStyle]}
          numberOfLines={1}
        >
          {name}
        </Text>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  name: {
    fontSize: 20,
    fontWeight: '800',
    color: colors.baytgo,
    letterSpacing: -0.5,
  },
  nameLight: { color: colors.white },
});
