import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Clock } from 'lucide-react-native';
import { useAuth } from '../context/AuthContext';
import TabPageHeader from '../components/TabPageHeader';
import { Card } from '../ui';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';

export default function MuthowifPendingScreen() {
  const { user } = useAuth();

  return (
    <View style={styles.safe}>
      <TabPageHeader title="Beranda" subtitle={`Halo, ${user?.name || 'Muthowif'}`} />
      <View style={styles.content}>
        <Card style={styles.card} padding={spacing['2xl']} elevated={false}>
          <View style={styles.iconWrap}>
            <Clock size={32} color={colors.warning} strokeWidth={1.8} />
          </View>
          <Text style={styles.title}>Profil sedang ditinjau</Text>
          <Text style={styles.body}>
            Pendaftaran muthowif Anda sudah kami terima. Tim admin akan memverifikasi dokumen dan profil Anda.
            Setelah disetujui, Anda bisa menerima permintaan booking lewat aplikasi.
          </Text>
          <View style={styles.hintBox}>
            <Text style={styles.hintTitle}>Sementara ini</Text>
            <Text style={styles.hintText}>
              • Pantau status lewat tab Profil{'\n'}• Anda akan mendapat notifikasi setelah disetujui
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
  hintBox: {
    marginTop: spacing.xl,
    backgroundColor: colors.background,
    borderRadius: radius.sm,
    padding: spacing.lg,
  },
  hintTitle: { ...typography.label, color: colors.baytgo, textTransform: 'uppercase' },
  hintText: { marginTop: spacing.sm, ...typography.caption, lineHeight: 20, color: colors.textSecondary },
});
