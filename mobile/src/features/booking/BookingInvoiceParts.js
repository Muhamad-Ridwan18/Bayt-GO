import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { BadgeCheck, Building2, CreditCard, FileText, Mail, Phone, User } from 'lucide-react-native';
import Card from '../../ui/Card';
import { colors, gradients, radius, shadows, spacing, typography } from '../../theme/tokens';
import { formatIdr } from '../../utils/format';
import { formatDateRange, serviceTypeLabel } from '../../utils/bookingLabels';

function PartyRow({ icon: Icon, label, value }) {
  return (
    <View style={styles.partyRow}>
      <View style={styles.partyIcon}>
        <Icon size={15} color={colors.baytgo} strokeWidth={2} />
      </View>
      <View style={styles.partyCopy}>
        <Text style={styles.partyLabel}>{label}</Text>
        <Text style={styles.partyValue}>{value || '—'}</Text>
      </View>
    </View>
  );
}

function LineItem({ label, value, bold, accent }) {
  return (
    <View style={[styles.lineItem, bold && styles.lineItemBold]}>
      <Text style={[styles.lineLabel, bold && styles.lineLabelBold]}>{label}</Text>
      <Text style={[styles.lineValue, bold && styles.lineValueBold, accent && styles.lineValueAccent]}>{value}</Text>
    </View>
  );
}

export function InvoiceDocument({ invoice }) {
  const paidAt = invoice.paid_at
    ? new Date(invoice.paid_at).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' })
    : '—';

  return (
    <Card style={styles.paper} padding={0} elevated>
      <LinearGradient colors={gradients.primary} style={styles.paperHeader}>
        <View style={styles.brandRow}>
          <View style={styles.brandMark}>
            <FileText size={20} color={colors.white} strokeWidth={2} />
          </View>
          <View>
            <Text style={styles.brandName}>Bayt-GO</Text>
            <Text style={styles.brandSub}>Invoice resmi pembayaran</Text>
          </View>
        </View>
        <View style={styles.paidBadge}>
          <BadgeCheck size={14} color={colors.success} strokeWidth={2.5} />
          <Text style={styles.paidBadgeText}>Lunas</Text>
        </View>
      </LinearGradient>

      <View style={styles.paperBody}>
        <View style={styles.amountBlock}>
          <Text style={styles.amountEyebrow}>Total dibayar</Text>
          <Text style={styles.amountValue}>{formatIdr(invoice.amounts?.total || 0)}</Text>
          <Text style={styles.amountMeta}>{invoice.booking_code}</Text>
          <Text style={styles.amountDate}>Dibayar {paidAt}</Text>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Pelanggan</Text>
          <PartyRow icon={User} label="Nama" value={invoice.customer?.name} />
          <PartyRow icon={Mail} label="Email" value={invoice.customer?.email} />
          <PartyRow icon={Phone} label="Telepon" value={invoice.customer?.phone} />
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Layanan</Text>
          <PartyRow icon={User} label="Muthowif" value={invoice.muthowif?.name} />
          <PartyRow
            icon={Building2}
            label="Periode"
            value={formatDateRange(invoice.service_period?.starts_on, invoice.service_period?.ends_on)}
          />
          <PartyRow icon={User} label="Jamaah" value={String(invoice.pilgrim_count || '—')} />
          <PartyRow icon={Building2} label="Tipe layanan" value={serviceTypeLabel(invoice.service_type)} />
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Rincian biaya</Text>
          <View style={styles.linesBox}>
            <LineItem label="Biaya layanan" value={formatIdr(invoice.amounts?.base || 0)} />
            <LineItem label="Biaya platform" value={formatIdr(invoice.amounts?.platform_fee || 0)} />
            <LineItem label="Total dibayar" value={formatIdr(invoice.amounts?.total || 0)} bold accent />
          </View>
        </View>

        {invoice.payment ? (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Pembayaran</Text>
            <PartyRow icon={CreditCard} label="Order ID" value={invoice.payment.order_id} />
            <PartyRow icon={CreditCard} label="Metode" value={invoice.payment.payment_type} />
          </View>
        ) : null}

        <Text style={styles.footerNote}>
          Dokumen ini diterbitkan secara elektronik oleh Bayt-GO dan sah tanpa tanda tangan basah.
        </Text>
      </View>
    </Card>
  );
}

const styles = StyleSheet.create({
  paper: { overflow: 'hidden', ...shadows.md },
  paperHeader: {
    padding: spacing.xl,
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: spacing.md,
  },
  brandRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, flex: 1 },
  brandMark: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  brandName: { ...typography.subtitle, color: colors.white },
  brandSub: { marginTop: 2, ...typography.small, color: 'rgba(255,255,255,0.75)', fontWeight: '500' },
  paidBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: colors.white,
    borderRadius: radius.full,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
  },
  paidBadgeText: { ...typography.label, color: colors.success },
  paperBody: { padding: spacing.xl, backgroundColor: colors.card },
  amountBlock: {
    alignItems: 'center',
    paddingVertical: spacing.lg,
    marginBottom: spacing.lg,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  amountEyebrow: { ...typography.label, color: colors.textMuted, textTransform: 'uppercase' },
  amountValue: { marginTop: spacing.sm, ...typography.hero, fontSize: 30, color: colors.baytgo },
  amountMeta: { marginTop: spacing.sm, ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  amountDate: { marginTop: spacing.xs, ...typography.small, color: colors.textSecondary, fontWeight: '500' },
  section: { marginBottom: spacing.xl },
  sectionTitle: {
    ...typography.label,
    color: colors.baytgo,
    textTransform: 'uppercase',
    marginBottom: spacing.md,
  },
  partyRow: { flexDirection: 'row', gap: spacing.md, marginBottom: spacing.md },
  partyIcon: {
    width: 34,
    height: 34,
    borderRadius: 10,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  partyCopy: { flex: 1 },
  partyLabel: { ...typography.label, color: colors.textMuted },
  partyValue: { marginTop: 2, ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.textPrimary },
  linesBox: {
    backgroundColor: colors.surface,
    borderRadius: radius.sm,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
  },
  lineItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: spacing.md,
    paddingVertical: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  lineItemBold: { borderBottomWidth: 0, paddingTop: spacing.md, marginTop: spacing.xs },
  lineLabel: { ...typography.caption, color: colors.textSecondary },
  lineLabelBold: { fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.textPrimary },
  lineValue: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.textPrimary },
  lineValueBold: { fontSize: 16, color: colors.baytgo },
  lineValueAccent: { color: colors.baytgo },
  footerNote: {
    ...typography.small,
    color: colors.textMuted,
    textAlign: 'center',
    lineHeight: 18,
    fontWeight: '500',
    marginTop: spacing.sm,
  },
});
