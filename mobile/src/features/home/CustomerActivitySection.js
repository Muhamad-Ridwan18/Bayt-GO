import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Button, Card, PressableScale } from '../../ui';
import { colors, gradients, layout, radius, spacing, typography } from '../../theme/tokens';
import UpcomingTripCard from './UpcomingTripCard';
import { useLocale } from '../../utils/locale';

export default function CustomerActivitySection({
  stats = [],
  booking,
  onStatPress,
  onSeeAll,
  onBookingPress,
  onPay,
  onChat,
  onExplore,
}) {
  const locale = useLocale();
  const isEn = locale === 'en';
  return (
    <View style={styles.wrap}>
      {stats.length > 0 ? (
        <LinearGradient
          colors={gradients.primarySoft}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.statsCard}
        >
          <Text style={styles.statsTitle}>{isEn ? 'Activity summary' : 'Ringkasan aktivitas'}</Text>
          <Text style={styles.statsSub}>{isEn ? 'A quick overview of your trips and tickets.' : 'Ikhtisar singkat perjalanan dan tiket Anda.'}</Text>
          <View style={styles.statsGrid}>
            {stats.map((stat) => (
              <PressableScale
                key={stat.label}
                onPress={() => onStatPress?.(stat)}
                haptic="light"
                style={styles.statCell}
              >
                <Text style={styles.statValue}>{stat.value}</Text>
                <Text style={styles.statLabel}>{stat.label}</Text>
              </PressableScale>
            ))}
          </View>
        </LinearGradient>
      ) : null}

      <View style={styles.upcomingHead}>
        <Text style={styles.upcomingTitle}>{isEn ? 'Upcoming trip' : 'Perjalanan mendatang'}</Text>
        {onSeeAll ? (
          <PressableScale onPress={onSeeAll} haptic="light">
            <Text style={styles.seeAll}>{isEn ? 'See all' : 'Lihat semua'}</Text>
          </PressableScale>
        ) : null}
      </View>

      {booking ? (
        <UpcomingTripCard booking={booking} onPress={onBookingPress} onPay={onPay} onChat={onChat} />
      ) : (
        <Card style={styles.emptyCard} padding={spacing.xl} elevated={false} variant="flat">
          <Text style={styles.emptyText}>
            {isEn
              ? 'No upcoming trips yet. Find a muthowif for your dates.'
              : 'Belum ada jadwal mendatang. Temukan muthowif untuk tanggal Anda.'}
          </Text>
          <Button label={isEn ? 'Explore muthowif' : 'Jelajahi muthowif'} onPress={onExplore} style={styles.emptyCta} />
        </Card>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: {
    marginTop: spacing.xl,
    paddingHorizontal: layout.screenPadding,
    gap: spacing.lg,
  },
  statsCard: {
    borderRadius: radius.md,
    padding: spacing.lg,
  },
  statsTitle: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
    color: colors.white,
  },
  statsSub: {
    marginTop: spacing.xs,
    ...typography.small,
    color: 'rgba(255,255,255,0.75)',
  },
  statsGrid: {
    marginTop: spacing.md,
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  statCell: {
    width: '48%',
    flexGrow: 1,
    backgroundColor: 'rgba(255,255,255,0.12)',
    borderRadius: radius.sm,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.15)',
  },
  statValue: {
    ...typography.subtitle,
    fontSize: 22,
    color: colors.white,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
  },
  statLabel: {
    marginTop: 2,
    ...typography.small,
    color: 'rgba(255,255,255,0.85)',
  },
  upcomingHead: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  upcomingTitle: {
    ...typography.subtitle,
    fontSize: 17,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
  },
  seeAll: { ...typography.caption, fontWeight: '800', color: colors.goldMuted },
  emptyCard: { borderStyle: 'dashed' },
  emptyText: {
    ...typography.caption,
    color: colors.textSecondary,
    textAlign: 'center',
    lineHeight: 20,
  },
  emptyCta: { marginTop: spacing.md },
});
