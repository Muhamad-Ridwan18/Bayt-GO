import React from 'react';
import { Linking, ScrollView, StyleSheet, Switch, Text, TextInput, View } from 'react-native';
import { Check, Minus, Plus } from 'lucide-react-native';
import { Button, Card, FilterSheet, PressableScale } from '../../ui';
import { colors, radius, spacing, typography } from '../../theme/tokens';
import { formatIdr } from '../../utils/format';
import { WEB_BASE_URL } from '../../config/api';
import { useLocale } from '../../utils/locale';

const TC_POINTS = {
  id: [
    'Permintaan pesanan bersifat mengikat setelah muthowif menyetujui dan Anda menyelesaikan pembayaran sesuai ketentuan BaytGo.',
    'Data dan dokumen yang Anda unggah digunakan untuk verifikasi layanan; pastikan tiket dan dokumen sesuai jadwal perjalanan.',
    'Muthowif dapat menolak atau membatalkan jika jadwal bentrok, tidak tersedia, atau tidak memenuhi kebijakan.',
    'Pembayaran, pembatalan, refund, dan reschedule mengikuti kebijakan platform dan yang tercantum pada halaman pesanan.',
    'Dengan melanjutkan, Anda menyatakan data yang diisi benar dan memahami alur persetujuan serta pembayaran.',
  ],
  en: [
    'Your booking request becomes binding after the muthowif approves it and you complete payment under BaytGo terms.',
    'The data and documents you upload are used for service verification; make sure tickets and documents match your travel schedule.',
    'The muthowif may reject or cancel if the schedule conflicts, they are unavailable, or the request does not meet policy.',
    'Payment, cancellation, refund, and reschedule follow platform policies and what is stated on the order page.',
    'By continuing, you confirm the submitted data is correct and you understand the approval and payment flow.',
  ],
};

export function ServiceOption({ label, active, price, onPress }) {
  return (
    <PressableScale onPress={onPress} haptic="light" style={styles.serviceOptionWrap}>
      <Card
        style={[styles.serviceOption, active && styles.serviceOptionActive]}
        padding={spacing.lg}
        elevated={false}
      >
        <Text style={[styles.serviceOptionText, active && styles.serviceOptionTextActive]}>{label}</Text>
        <Text style={styles.serviceOptionPrice}>{formatIdr(price)} / hari</Text>
      </Card>
    </PressableScale>
  );
}

export function StepBadges({ step }) {
  const locale = useLocale();
  const isEn = locale === 'en';
  return (
    <View style={styles.stepRow}>
      <View style={[styles.stepBadge, step === 1 && styles.stepBadgeActive]}>
        <Text style={[styles.stepBadgeText, step === 1 && styles.stepBadgeTextActive]}>1 {isEn ? 'Service' : 'Layanan'}</Text>
      </View>
      <View style={[styles.stepBadge, step === 2 && styles.stepBadgeActive]}>
        <Text style={[styles.stepBadgeText, step === 2 && styles.stepBadgeTextActive]}>2 {isEn ? 'Documents' : 'Dokumen'}</Text>
      </View>
    </View>
  );
}

export function PilgrimCounter({ value, minPax, maxPax, onChange }) {
  const locale = useLocale();
  const isEn = locale === 'en';
  const count = parseInt(value, 10) || minPax;

  const handleChangeText = (text) => {
    const digits = text.replace(/\D/g, '');
    if (!digits) {
      onChange('');
      return;
    }
    const num = parseInt(digits, 10);
    onChange(String(Math.min(maxPax, num)));
  };

  const handleBlur = () => {
    const num = parseInt(value, 10);
    if (!value || Number.isNaN(num) || num < minPax) {
      onChange(String(minPax));
      return;
    }
    if (num > maxPax) {
      onChange(String(maxPax));
    }
  };

  return (
    <>
      <View style={styles.counterRow}>
        <PressableScale
          onPress={() => onChange(String(Math.max(minPax, count - 1)))}
          haptic="light"
          style={styles.counterBtn}
        >
          <Minus size={20} color={colors.baytgo} strokeWidth={2.5} />
        </PressableScale>
        <TextInput
          style={styles.counterInput}
          value={value}
          onChangeText={handleChangeText}
          onBlur={handleBlur}
          keyboardType="number-pad"
          maxLength={String(maxPax).length}
          selectTextOnFocus
          placeholder={String(minPax)}
          placeholderTextColor={colors.textMuted}
        />
        <PressableScale
          onPress={() => onChange(String(Math.min(maxPax, count + 1)))}
          haptic="light"
          style={styles.counterBtn}
        >
          <Plus size={20} color={colors.baytgo} strokeWidth={2.5} />
        </PressableScale>
      </View>
      <Text style={styles.hint}>
        {isEn ? `Min ${minPax}, max ${maxPax} pilgrims — you can type directly` : `Min ${minPax}, max ${maxPax} jamaah — bisa ketik langsung`}
      </Text>
    </>
  );
}

export function ToggleRow({ label, value, onValueChange }) {
  return (
    <Card style={styles.switchRow} padding={spacing.lg} elevated={false}>
      <Text style={styles.switchLabel}>{label}</Text>
      <Switch value={value} onValueChange={onValueChange} trackColor={{ true: colors.baytgo }} />
    </Card>
  );
}

function AddonChoiceOption({ label, selected, onPress }) {
  return (
    <PressableScale onPress={onPress} haptic="light" style={styles.choiceOptionWrap}>
      <Card
        style={[styles.choiceOption, selected && styles.choiceOptionActive]}
        padding={spacing.md}
        elevated={false}
      >
        <View style={[styles.choiceRadio, selected && styles.choiceRadioActive]}>
          {selected ? <View style={styles.choiceRadioDot} /> : null}
        </View>
        <Text style={[styles.choiceOptionText, selected && styles.choiceOptionTextActive]}>{label}</Text>
      </Card>
    </PressableScale>
  );
}

export function AddonChoiceGroup({ question, value, options, onChange }) {
  return (
    <View style={styles.choiceGroup}>
      <Text style={styles.choiceQuestion}>{question}</Text>
      <View style={styles.choiceRow}>
        {options.map((option) => (
          <AddonChoiceOption
            key={option.value}
            label={option.label}
            selected={value === option.value}
            onPress={() => onChange(option.value)}
          />
        ))}
      </View>
    </View>
  );
}

export function BookingAddonChoices({
  canHotel,
  canTransport,
  hotelPricePerDay,
  transportPriceFlat,
  withSameHotel,
  withTransport,
  onHotelChange,
  onTransportChange,
}) {
  const locale = useLocale();
  const isEn = locale === 'en';
  if (!canHotel && !canTransport) return null;

  const hotelAmount = formatIdr(hotelPricePerDay).replace(/^Rp\s?/, '');

  return (
    <View style={styles.addonChoices}>
      {canHotel ? (
        <AddonChoiceGroup
          question={isEn ? 'Will you provide a hotel for the muthowif?' : 'Apakah Anda menyediakan hotel untuk Muthowif?'}
          value={withSameHotel ? 'no' : 'yes'}
          onChange={(next) => onHotelChange(next === 'no')}
          options={[
            { value: 'yes', label: isEn ? 'Yes' : 'Ya' },
            { value: 'no', label: isEn ? `No (+Rp ${hotelAmount}/day)` : `Tidak (+Rp ${hotelAmount}/hari)` },
          ]}
        />
      ) : null}
      {canTransport ? (
        <AddonChoiceGroup
          question={isEn ? 'Use muthowif pickup service?' : 'Gunakan layanan penyambutan Muthowif?'}
          value={withTransport ? 'yes' : 'no'}
          onChange={(next) => onTransportChange(next === 'yes')}
          options={[
            {
              value: 'yes',
              label: isEn ? `Yes (+${formatIdr(transportPriceFlat)} muthowif travel fee)` : `Ya (+${formatIdr(transportPriceFlat)} biaya perjalanan Muthowif)`,
            },
            { value: 'no', label: isEn ? 'No (Meet directly at the hotel)' : 'Tidak (Bertemu langsung di hotel)' },
          ]}
        />
      ) : null}
    </View>
  );
}

export function AddOnToggle({ addon, value, onValueChange }) {
  return (
    <ToggleRow
      label={`${addon.name} (+${formatIdr(addon.price)})`}
      value={value}
      onValueChange={onValueChange}
    />
  );
}

export function BookingEstimateCard({ estimate }) {
  const locale = useLocale();
  const isEn = locale === 'en';
  if (!estimate) return null;

  return (
    <Card style={styles.estimateCard} padding={spacing.lg} elevated={false}>
      <Text style={styles.estimateTitle}>{isEn ? 'Estimated cost' : 'Estimasi biaya'}</Text>
      {estimate.lines.map((line) => (
        <View key={line.key} style={styles.estimateRow}>
          <Text style={styles.estimateLabel}>{line.label}</Text>
          <Text style={styles.estimateValue}>{formatIdr(line.amount)}</Text>
        </View>
      ))}
      <View style={styles.estimateDivider} />
      <View style={styles.estimateRow}>
        <Text style={styles.estimateLabel}>{isEn ? 'Service subtotal' : 'Subtotal layanan'}</Text>
        <Text style={styles.estimateValue}>{formatIdr(estimate.base)}</Text>
      </View>
      {estimate.platform_fee > 0 ? (
        <View style={styles.estimateRow}>
          <Text style={styles.estimateLabel}>
            {isEn ? `Platform fee (${estimate.platform_fee_percent}%)` : `Biaya platform (${estimate.platform_fee_percent}%)`}
          </Text>
          <Text style={styles.estimateValue}>{formatIdr(estimate.platform_fee)}</Text>
        </View>
      ) : null}
      <View style={[styles.estimateRow, styles.estimateTotalRow]}>
        <Text style={styles.estimateTotalLabel}>{isEn ? 'Estimated total' : 'Total estimasi'}</Text>
        <Text style={styles.estimateTotalValue}>{formatIdr(estimate.total_payable)}</Text>
      </View>
      <Text style={styles.estimateHint}>{isEn ? 'Estimate before muthowif confirmation' : 'Perkiraan sebelum konfirmasi muthowif'}</Text>
    </Card>
  );
}

export function AddOnRow({ addon, selected, onPress }) {
  return (
    <PressableScale onPress={onPress} haptic="light">
      <Card style={[styles.addOnRow, selected && styles.addOnRowActive]} padding={spacing.lg} elevated={false}>
        <Text style={styles.addOnName}>{addon.name}</Text>
        <Text style={styles.addOnPrice}>{formatIdr(addon.price)}</Text>
      </Card>
    </PressableScale>
  );
}

export function SectionTitle({ children }) {
  return <Text style={styles.sectionTitle}>{children}</Text>;
}

export function FormError({ message }) {
  if (!message) return null;
  return (
    <Card style={styles.errorCard} padding={spacing.md} elevated={false}>
      <Text style={styles.errorText}>{message}</Text>
    </Card>
  );
}

export function TermsConsentSheet({
  visible,
  agreed,
  loading,
  onClose,
  onAgreeChange,
  onConfirm,
}) {
  const locale = useLocale();
  const isEn = locale === 'en';
  const points = TC_POINTS[locale] || TC_POINTS.id;
  return (
    <FilterSheet
      visible={visible}
      onClose={onClose}
      title={isEn ? 'Terms & conditions' : 'Syarat & ketentuan'}
      footer={(
        <>
          <Button label={isEn ? 'Cancel' : 'Batal'} variant="secondary" onPress={onClose} />
          <Button
            label={isEn ? 'Agree and submit' : 'Setuju dan kirim'}
            onPress={onConfirm}
            disabled={!agreed}
            loading={loading}
          />
        </>
      )}
    >
      <ScrollView style={styles.tcScroll} showsVerticalScrollIndicator={false}>
        {points.map((point) => (
          <View key={point} style={styles.tcPoint}>
            <Text style={styles.tcBullet}>•</Text>
            <Text style={styles.tcPointText}>{point}</Text>
          </View>
        ))}
        <PressableScale
          onPress={() => Linking.openURL(`${WEB_BASE_URL.replace(/\/$/, '')}/terms`)}
          haptic="light"
        >
          <Text style={styles.tcLink}>{isEn ? 'Read the full terms and conditions' : 'Baca syarat dan ketentuan lengkap'}</Text>
        </PressableScale>
        <PressableScale
          onPress={() => onAgreeChange(!agreed)}
          haptic="light"
          style={styles.tcCheckRow}
        >
          <View style={[styles.tcBox, agreed && styles.tcBoxOn]}>
            {agreed ? <Check size={14} color={colors.white} strokeWidth={3} /> : null}
          </View>
          <Text style={styles.tcCheckLabel}>{isEn ? 'I have read and agree to the terms and conditions above.' : 'Saya telah membaca dan menyetujui syarat & ketentuan di atas.'}</Text>
        </PressableScale>
      </ScrollView>
    </FilterSheet>
  );
}

const styles = StyleSheet.create({
  serviceOptionWrap: { flex: 1 },
  serviceOption: { borderColor: colors.border },
  serviceOptionActive: { borderColor: colors.baytgo, backgroundColor: colors.successLight },
  serviceOptionText: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', fontWeight: '800', color: colors.slate700 },
  serviceOptionTextActive: { color: colors.baytgo },
  serviceOptionPrice: { marginTop: spacing.xs, ...typography.small, color: colors.textSecondary },
  stepRow: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.lg, marginBottom: spacing.md },
  stepBadge: {
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
    borderRadius: radius.full,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border,
  },
  stepBadgeActive: { backgroundColor: colors.successLight, borderColor: colors.baytgo },
  stepBadgeText: { ...typography.small, color: colors.textMuted },
  stepBadgeTextActive: { color: colors.baytgo, fontFamily: 'PlusJakartaSans_700Bold' },
  counterRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.lg },
  counterBtn: {
    width: 44,
    height: 44,
    borderRadius: radius.sm,
    backgroundColor: colors.card,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  counterInput: {
    minWidth: 72,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.card,
    ...typography.title,
    fontSize: 24,
    color: colors.textPrimary,
    textAlign: 'center',
  },
  hint: { marginTop: spacing.sm, ...typography.small, color: colors.textSecondary, fontWeight: '500' },
  switchRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: spacing.md },
  switchLabel: { flex: 1, ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.slate700, paddingRight: spacing.md },
  addonChoices: { marginTop: spacing.md, gap: spacing.lg },
  choiceGroup: { gap: spacing.sm },
  choiceQuestion: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.slate800,
    lineHeight: 20,
  },
  choiceRow: { gap: spacing.sm },
  choiceOptionWrap: { width: '100%' },
  choiceOption: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
    borderColor: colors.border,
  },
  choiceOptionActive: { borderColor: colors.baytgo, backgroundColor: colors.successLight },
  choiceRadio: {
    width: 18,
    height: 18,
    borderRadius: 9,
    borderWidth: 2,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 2,
  },
  choiceRadioActive: { borderColor: colors.baytgo },
  choiceRadioDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: colors.baytgo,
  },
  choiceOptionText: { flex: 1, ...typography.small, color: colors.textSecondary, lineHeight: 18 },
  choiceOptionTextActive: { color: colors.baytgo, fontFamily: 'PlusJakartaSans_700Bold' },
  addOnRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: spacing.sm, borderColor: colors.border },
  addOnRowActive: { borderColor: colors.baytgo, backgroundColor: colors.successLight },
  addOnName: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.slate800 },
  addOnPrice: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  estimateCard: { marginTop: spacing.lg, backgroundColor: colors.card, borderColor: colors.border },
  estimateTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo, marginBottom: spacing.md },
  estimateRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: spacing.md, paddingVertical: spacing.xs },
  estimateLabel: { flex: 1, ...typography.small, color: colors.textSecondary, fontWeight: '600' },
  estimateValue: { ...typography.small, color: colors.textPrimary, fontWeight: '700' },
  estimateDivider: { height: 1, backgroundColor: colors.border, marginVertical: spacing.sm },
  estimateTotalRow: { paddingTop: spacing.xs },
  estimateTotalLabel: { flex: 1, ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.slate800 },
  estimateTotalValue: { ...typography.subtitle, color: colors.baytgo, fontWeight: '800' },
  estimateHint: { marginTop: spacing.sm, ...typography.small, color: colors.textMuted, fontWeight: '500' },
  sectionTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo, marginTop: spacing.lg, marginBottom: spacing.md },
  errorCard: { backgroundColor: colors.errorLight, borderColor: '#FECACA', marginBottom: spacing.md },
  errorText: { ...typography.caption, color: colors.error, fontWeight: '600' },
  tcScroll: { maxHeight: 360 },
  tcPoint: { flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.sm },
  tcBullet: { ...typography.caption, color: colors.baytgo, lineHeight: 20 },
  tcPointText: { flex: 1, ...typography.caption, color: colors.slate700, lineHeight: 20, fontWeight: '500' },
  tcLink: {
    marginTop: spacing.sm,
    marginBottom: spacing.md,
    ...typography.caption,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_700Bold',
    textDecorationLine: 'underline',
  },
  tcCheckRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
    padding: spacing.md,
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.background,
  },
  tcBox: {
    width: 22,
    height: 22,
    borderRadius: 6,
    borderWidth: 2,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 1,
    backgroundColor: colors.white,
  },
  tcBoxOn: { backgroundColor: colors.baytgo, borderColor: colors.baytgo },
  tcCheckLabel: { flex: 1, ...typography.caption, color: colors.slate800, fontWeight: '600', lineHeight: 20 },
});
