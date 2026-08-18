import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Clock, Compass, User } from 'lucide-react-native';
import { useAuth } from '../context/AuthContext';
import TabPageHeader from '../components/TabPageHeader';
import { Button, Card } from '../ui';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { useLocale } from '../utils/locale';

export default function MuthowifPendingScreen({ navigation }) {
  const { user } = useAuth();
  const locale = useLocale(); const isEn = locale === 'en';

  return (
    <View style={styles.safe}>
      <TabPageHeader title={isEn ? 'Home' : 'Beranda'} subtitle={`${isEn ? 'Hello' : 'Halo'}, ${user?.name || 'Muthowif'}`} />
      <View style={styles.content}>
        <Card style={styles.card} padding={spacing['2xl']} elevated={false}>
          <View style={styles.iconWrap}>
            <Clock size={32} color={colors.warning} strokeWidth={1.8} />
          </View>
          <Text style={styles.kicker}>{isEn ? 'Muthowif account under review' : 'Akun muthowif sedang ditinjau'}</Text>
          <Text style={styles.title}>{isEn ? 'Hello' : 'Halo'}, {user?.name || 'Muthowif'}</Text>
          <Text style={styles.body}>
            {isEn ? 'The admin team will verify your documents. Once approved, you get full access to services, schedule, and wallet balance.' : 'Tim admin akan memverifikasi dokumen Anda. Setelah disetujui, Anda mendapat akses penuh layanan, jadwal, dan saldo dompet.'}
          </Text>

          <View style={styles.actions}>
            <Button
              label={isEn ? 'View marketplace' : 'Lihat marketplace'}
              onPress={() => navigation.navigate('Directory')}
              icon={<Compass size={18} color={colors.white} strokeWidth={2} />}
            />
            <Button
              label={isEn ? 'Profile' : 'Profil'}
              variant="secondary"
              onPress={() => navigation.getParent()?.navigate('ProfileTab')}
              icon={<User size={18} color={colors.baytgo} strokeWidth={2} />}
            />
          </View>

          <View style={styles.hintBox}>
            <Text style={styles.hintTitle}>{isEn ? 'In the meantime' : 'Sementara ini'}</Text>
            <Text style={styles.hintText}>
              {isEn ? '• Complete your profile and documents in the Profile tab\n• You will be notified once approved' : '• Lengkapi profil dan dokumen di tab Profil\n• Anda akan mendapat notifikasi setelah disetujui'}
            </Text>
          </View>
        </Card>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  content: { padding: layout.screenPadding, paddingBottom: spacing['3xl'] },
  card: { borderRadius: radius.lg, borderColor: '#FDE68A' },
  iconWrap: {
    width: 64,
    height: 64,
    borderRadius: radius.md - 4,
    backgroundColor: colors.warningLight,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: spacing.lg,
  },
  kicker: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.warning,
  },
  title: {
    ...typography.title,
    fontSize: 22,
    color: colors.textPrimary,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
  },
  body: {
    marginTop: spacing.md - 2,
    ...typography.caption,
    lineHeight: 22,
    color: colors.textSecondary,
  },
  actions: { marginTop: spacing.xl, gap: spacing.md },
  hintBox: {
    marginTop: spacing.xl,
    backgroundColor: colors.background,
    borderRadius: radius.sm,
    padding: spacing.lg,
  },
  hintTitle: { ...typography.label, color: colors.baytgo, textTransform: 'uppercase' },
  hintText: { marginTop: spacing.sm, ...typography.caption, lineHeight: 20, color: colors.textSecondary },
});
