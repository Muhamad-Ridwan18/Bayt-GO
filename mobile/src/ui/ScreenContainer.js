import React from 'react';
import { StyleSheet, View } from 'react-native';
import { colors } from '../theme/tokens';

export default function ScreenContainer({
  children,
  style,
  background = colors.background,
}) {
  return (
    <View
      style={[
        styles.base,
        { backgroundColor: background },
        style,
      ]}
    >
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  base: { flex: 1 },
});
