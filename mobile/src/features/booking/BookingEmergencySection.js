import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { AlertTriangle } from 'lucide-react-native';
import AppImage from '../../ui/AppImage';
import Button from '../../ui/Button';
import BookingSection from './BookingSection';
import { colors, radius, spacing, typography } from '../../theme/tokens';
import { resolveMediaUrl } from '../../utils/mediaUrl';

export default function BookingEmergencySection({
  booking,
  emergency,
  muthowifName,
  onReport,
  onSelectReplacement,
}) {
  const emergencyReport = emergency.report;
  const replacementOffers = emergency.replacement_offers || [];

  return (
    <BookingSection variant="danger" style={styles.section}>
      <View style={styles.header}>
        <AlertTriangle size={20} color={colors.error} strokeWidth={2} />
        <Text style={styles.title}>Insiden Darurat</Text>
      </View>

      {emergency.has_replacement ? (
        <View style={styles.success}>
          <Text style={styles.successTitle}>Pengganti muthowif aktif</Text>
          <Text style={styles.successText}>Layanan dilanjutkan dengan {muthowifName}.</Text>
        </View>
      ) : emergencyReport ? (
        <>
          <Text style={styles.status}>
            {emergencyReport.case_type_label} · {emergencyReport.status_label}
          </Text>
          {emergencyReport.description ? (
            <Text style={styles.desc}>{emergencyReport.description}</Text>
          ) : null}

          {['submitted', 'under_review'].includes(emergencyReport.status) ? (
            <Text style={styles.hint}>Tim Bayt-GO sedang meninjau laporan Anda.</Text>
          ) : null}

          {emergencyReport.status === 'verified' && replacementOffers.length === 0 ? (
            <Text style={styles.hint}>Menunggu muthowif pengganti tersedia.</Text>
          ) : null}

          {replacementOffers.length > 0 ? (
            <View style={styles.candidates}>
              <Text style={styles.candidateHeading}>Pilih muthowif pengganti</Text>
              {replacementOffers.map((offer) => (
                <View key={offer.id} style={styles.candidateCard}>
                  <View style={styles.candidateRow}>
                    <AppImage uri={resolveMediaUrl(offer.muthowif?.avatar)} size={48} rounded={radius.sm} />
                    <View style={styles.candidateMeta}>
                      <Text style={styles.candidateName}>{offer.muthowif?.name}</Text>
                      {offer.muthowif?.rating ? (
                        <Text style={styles.candidateRating}>
                          ★ {offer.muthowif.rating} ({offer.muthowif.reviews_count || 0})
                        </Text>
                      ) : null}
                    </View>
                  </View>
                  <Button
                    label="Pilih muthowif ini"
                    onPress={() => onSelectReplacement(offer)}
                    size="sm"
                  />
                </View>
              ))}
            </View>
          ) : null}
        </>
      ) : booking.can_report_emergency ? (
        <>
          <Text style={styles.hint}>
            Laporkan jika muthowif tidak dapat dihubungi, meninggalkan tugas, atau melanggar kesepakatan.
          </Text>
          <Button label="Lapor Insiden Darurat" onPress={onReport} variant="danger" />
        </>
      ) : null}
    </BookingSection>
  );
}

const styles = StyleSheet.create({
  section: { marginBottom: spacing.md },
  header: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginBottom: spacing.md },
  title: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.error },
  status: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: '#92400E' },
  desc: { marginTop: spacing.sm, ...typography.caption, color: colors.slate700, lineHeight: 20 },
  hint: { marginTop: spacing.md, ...typography.small, color: colors.textSecondary, lineHeight: 18 },
  success: {
    backgroundColor: colors.successLight,
    borderRadius: radius.sm,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: '#A7F3D0',
  },
  successTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.success },
  successText: { marginTop: spacing.xs, ...typography.small, color: colors.slate700 },
  candidates: { marginTop: spacing.lg, gap: spacing.md },
  candidateHeading: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.textPrimary },
  candidateCard: {
    backgroundColor: colors.card,
    borderRadius: radius.sm,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: '#A7F3D0',
    gap: spacing.md,
  },
  candidateRow: { flexDirection: 'row', gap: spacing.md, alignItems: 'center' },
  candidateMeta: { flex: 1 },
  candidateName: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.textPrimary },
  candidateRating: { marginTop: 2, ...typography.small, color: '#92400E' },
});
