import React, { useCallback } from 'react';
import { StyleSheet, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import ScreenHeader from '../components/ScreenHeader';
import SuccessState from '../ui/SuccessState';
import Button from '../ui/Button';
import { colors, layout, spacing } from '../theme/tokens';
import { navigateRoot } from '../navigation/rootNavigation';

function runTarget(navigation, target) {
  if (!target) {
    navigation.goBack();
    return;
  }
  if (target === 'goBack') {
    navigation.goBack();
    return;
  }
  if (typeof target === 'string') {
    navigation.navigate(target);
    return;
  }
  if (target.replace) {
    navigation.replace(target.name, target.params);
    return;
  }
  if (target.root) {
    navigateRoot(navigation, target.name, target.params);
    return;
  }
  navigation.navigate(target.name, target.params);
}

export default function SuccessScreen({ navigation, route }) {
  const {
    title = 'Berhasil',
    description,
    primaryLabel = 'Lanjut',
    primaryTarget,
    secondaryLabel,
    secondaryTarget,
  } = route.params || {};

  const onPrimary = useCallback(() => {
    runTarget(navigation, primaryTarget);
  }, [navigation, primaryTarget]);

  const onSecondary = useCallback(() => {
    runTarget(navigation, secondaryTarget);
  }, [navigation, secondaryTarget]);

  return (
    <View style={styles.container}>
      <ScreenHeader title="" onBack={() => navigation.goBack()} />
      <View style={styles.body}>
        <SuccessState title={title} description={description} />
        <View style={styles.actions}>
          {primaryLabel ? <Button label={primaryLabel} onPress={onPrimary} /> : null}
          {secondaryLabel ? (
            <Button label={secondaryLabel} variant="secondary" onPress={onSecondary} />
          ) : null}
        </View>
      </View>
      <LinearGradient
        colors={['rgba(248,250,252,0)', colors.background]}
        style={styles.fade}
        pointerEvents="none"
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  body: {
    flex: 1,
    paddingHorizontal: layout.screenPadding,
    justifyContent: 'center',
    paddingBottom: spacing['5xl'],
  },
  actions: { marginTop: spacing['2xl'], gap: spacing.md },
  fade: { position: 'absolute', bottom: 0, left: 0, right: 0, height: 80 },
});
