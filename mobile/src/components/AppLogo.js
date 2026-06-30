import React from 'react';
import { View, Text, StyleSheet, Image } from 'react-native';
import { colors } from '../theme/colors';

const FALLBACK_LOGO = require('../../assets/logo.png');

export default function AppLogo({ url, name = 'BaytGo', size = 36, showName = false, nameStyle }) {
  const source = url ? { uri: url } : FALLBACK_LOGO;

  return (
    <View style={styles.row}>
      <Image
        source={source}
        style={{ width: size, height: size, borderRadius: Math.round(size * 0.28) }}
        resizeMode="contain"
      />
      {showName ? (
        <Text style={[styles.name, nameStyle]} numberOfLines={1}>
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
});
