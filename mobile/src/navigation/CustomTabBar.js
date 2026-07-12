import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import Animated, {
  useAnimatedStyle,
  withSpring,
} from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import {
  ClipboardList,
  HelpCircle,
  MessageCircle,
  Receipt,
  Search,
  User,
  Wallet,
} from 'lucide-react-native';
import PressableScale from '../ui/PressableScale';
import { colors, layout, motion, radius, spacing, typography } from '../theme/tokens';

const ICONS = {
  HomeTab: Search,
  BookingsTab: Receipt,
  MuthowifBookingsTab: ClipboardList,
  WalletTab: Wallet,
  ChatTab: MessageCircle,
  SupportTab: HelpCircle,
  ProfileTab: User,
};

function TabItem({ route, isFocused, label, badge, onPress, onLongPress }) {
  const Icon = ICONS[route.name] || User;
  const iconColor = isFocused ? colors.baytgo : colors.slate600;

  const iconStyle = useAnimatedStyle(() => ({
    transform: [{ scale: withSpring(isFocused ? 1.04 : 1, motion.spring) }],
  }));

  return (
    <PressableScale
      onPress={onPress}
      onLongPress={onLongPress}
      haptic="light"
      scaleTo={0.92}
      style={styles.item}
    >
      <Animated.View style={[styles.iconWrap, isFocused && styles.iconWrapActive, iconStyle]}>
        <Icon size={22} color={iconColor} strokeWidth={isFocused ? 2.5 : 2} />
        {badge ? (
          <View style={styles.badge}>
            <Text style={styles.badgeText}>{badge}</Text>
          </View>
        ) : null}
      </Animated.View>
      <Text style={[styles.label, isFocused && styles.labelActive]} numberOfLines={1}>
        {label}
      </Text>
    </PressableScale>
  );
}

export default function CustomTabBar({ state, descriptors, navigation }) {
  const insets = useSafeAreaInsets();

  return (
    <View style={[styles.container, { paddingBottom: Math.max(insets.bottom, spacing.sm) }]}>
      <View style={styles.row}>
        {state.routes.map((route, index) => {
          const { options } = descriptors[route.key];
          const label = options.tabBarLabel ?? options.title ?? route.name;
          const isFocused = state.index === index;
          const badge = options.tabBarBadge;

          const onPress = () => {
            const event = navigation.emit({
              type: 'tabPress',
              target: route.key,
              canPreventDefault: true,
            });
            if (!isFocused && !event.defaultPrevented) {
              navigation.navigate(route.name);
            }
          };

          return (
            <TabItem
              key={route.key}
              route={route}
              isFocused={isFocused}
              label={label}
              badge={badge}
              onPress={onPress}
              onLongPress={() => navigation.emit({ type: 'tabLongPress', target: route.key })}
            />
          );
        })}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: colors.white,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    minHeight: layout.navBar.height,
    paddingTop: spacing.sm,
    paddingHorizontal: spacing.sm,
  },
  item: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 5,
  },
  iconWrap: {
    width: 44,
    height: 34,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: radius.md,
  },
  iconWrapActive: {
    backgroundColor: colors.primaryLight,
  },
  label: {
    ...typography.label,
    fontSize: 10,
    color: colors.slate600,
    letterSpacing: 0.1,
  },
  labelActive: {
    color: colors.baytgo,
    fontWeight: '800',
  },
  badge: {
    position: 'absolute',
    top: -1,
    right: 4,
    minWidth: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: colors.error,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 4,
    borderWidth: 2,
    borderColor: colors.white,
  },
  badgeText: {
    color: colors.white,
    fontSize: 9,
    fontWeight: '800',
  },
});
