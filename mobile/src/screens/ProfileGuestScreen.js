import React from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { Calendar, LogIn, MessageCircle, Search, User } from 'lucide-react-native';
import TabPageHeader from '../components/TabPageHeader';
import { navigateRoot } from '../navigation/rootNavigation';
import Button from '../ui/Button';
import Card from '../ui/Card';
import PressableScale from '../ui/PressableScale';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';

const FEATURES = [
  { icon: Search, title: 'Cari Muthowif', sub: 'Temukan pendamping ibadah terpercaya' },
  { icon: Calendar, title: 'Kelola Booking', sub: 'Pantau pesanan dan jadwal perjalanan' },
  { icon: MessageCircle, title: 'Chat Langsung', sub: 'Komunikasi dengan muthowif sebelum pesan' },
];

export default function ProfileGuestScreen({ navigation }) {
  return (
    <View style={styles.container}>
      <TabPageHeader title="Profil" subtitle="Masuk untuk mengelola akun Anda" />

      <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
        <Card style={styles.heroCard} padding={spacing['2xl']} elevated>
          <View style={styles.avatar}>
            <User size={40} color={colors.gold} strokeWidth={1.8} />
          </View>
          <Text style={styles.title}>Selamat datang di BaytGo</Text>
          <Text style={styles.sub}>
            Masuk atau daftar untuk memesan muthowif dan mengelola perjalanan ibadah Anda.
          </Text>

          <View style={styles.primaryBtn}>
            <Button
              label="Masuk"
              onPress={() => navigateRoot(navigation, 'Login')}
              icon={<LogIn size={18} color={colors.white} strokeWidth={2} />}
            />
          </View>

          <View style={styles.secondaryBtn}>
            <Button
              label="Daftar sebagai Jamaah"
              onPress={() => navigateRoot(navigation, 'Register', { role: 'customer' })}
              variant="secondary"
            />
          </View>

          <PressableScale
            onPress={() => navigateRoot(navigation, 'Register', { role: 'muthowif' })}
            haptic="light"
          >
            <Text style={styles.link}>Daftar sebagai Muthowif ›</Text>
          </PressableScale>
        </Card>

        <Text style={styles.sectionTitle}>Yang bisa Anda lakukan</Text>
        {FEATURES.map((feat) => {
          const Icon = feat.icon;
          return (
            <Card key={feat.title} style={styles.featureRow} padding={spacing.lg} elevated={false} variant="flat">
              <View style={styles.featureInner}>
                <View style={styles.featureIcon}>
                  <Icon size={20} color={colors.baytgo} strokeWidth={2} />
                </View>
                <View style={styles.featureCopy}>
                  <Text style={styles.featureTitle}>{feat.title}</Text>
                  <Text style={styles.featureSub}>{feat.sub}</Text>
                </View>
              </View>
            </Card>
          );
        })}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  scroll: {
    paddingHorizontal: layout.screenPadding,
    paddingTop: spacing.lg,
    paddingBottom: spacing.lg,
  },
  heroCard: {
    alignItems: 'center',
    marginBottom: spacing['2xl'],
    borderRadius: radius.md,
  },
  avatar: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.lg,
    borderWidth: 3,
    borderColor: colors.goldLight,
  },
  title: {
    ...typography.subtitle,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.baytgo,
    textAlign: 'center',
  },
  sub: {
    marginTop: spacing.sm,
    ...typography.caption,
    lineHeight: 21,
    color: colors.textSecondary,
    textAlign: 'center',
  },
  primaryBtn: { marginTop: spacing.xl },
  secondaryBtn: { marginTop: spacing.md },
  link: {
    marginTop: spacing.lg,
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.goldMuted,
  },
  sectionTitle: {
    ...typography.label,
    color: colors.textSecondary,
    textTransform: 'uppercase',
    marginBottom: spacing.md,
    marginLeft: spacing.xs,
  },
  featureRow: {
    marginBottom: spacing.md,
    borderRadius: radius.md,
  },
  featureInner: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
  },
  featureIcon: {
    width: 40,
    height: 40,
    borderRadius: radius.sm,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  featureCopy: { flex: 1 },
  featureTitle: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.textPrimary,
  },
  featureSub: {
    marginTop: spacing.xs,
    ...typography.small,
    fontWeight: '600',
    fontFamily: 'PlusJakartaSans_600SemiBold',
    color: colors.textSecondary,
    lineHeight: 17,
  },
});
