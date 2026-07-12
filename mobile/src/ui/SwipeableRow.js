import React, { useRef } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import Swipeable from 'react-native-gesture-handler/Swipeable';
import { RectButton } from 'react-native-gesture-handler';
import { colors, radius, spacing, typography } from '../theme/tokens';

function ActionButton({ label, color, backgroundColor, onPress, width = 88 }) {
  return (
    <RectButton
      style={[styles.action, { width, backgroundColor }]}
      onPress={onPress}
    >
      <Text style={[styles.actionText, { color }]}>{label}</Text>
    </RectButton>
  );
}

export default function SwipeableRow({
  children,
  leftActions = [],
  rightActions = [],
  enabled = true,
}) {
  const ref = useRef(null);

  const close = () => ref.current?.close();

  const renderActions = (actions, side) => (
    <View style={[styles.actions, side === 'left' ? styles.actionsLeft : styles.actionsRight]}>
      {actions.map((action) => (
        <ActionButton
          key={action.key}
          label={action.label}
          color={action.color || colors.white}
          backgroundColor={action.backgroundColor || colors.baytgo}
          width={action.width}
          onPress={() => {
            close();
            action.onPress?.();
          }}
        />
      ))}
    </View>
  );

  if (!enabled || (leftActions.length === 0 && rightActions.length === 0)) {
    return children;
  }

  return (
    <Swipeable
      ref={ref}
      friction={2}
      overshootFriction={8}
      renderLeftActions={leftActions.length ? () => renderActions(leftActions, 'left') : undefined}
      renderRightActions={rightActions.length ? () => renderActions(rightActions, 'right') : undefined}
    >
      {children}
    </Swipeable>
  );
}

const styles = StyleSheet.create({
  actions: { flexDirection: 'row', height: '100%' },
  actionsLeft: { marginRight: spacing.sm },
  actionsRight: { marginLeft: spacing.sm },
  action: {
    justifyContent: 'center',
    alignItems: 'center',
    borderRadius: radius.md,
    marginBottom: spacing.lg,
  },
  actionText: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    textAlign: 'center',
    paddingHorizontal: spacing.sm,
  },
});
