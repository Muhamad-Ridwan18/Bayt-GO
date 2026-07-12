import React from 'react';
import { StyleSheet, Text, TextInput, View } from 'react-native';
import {
  CheckCircle2, CirclePlus, Info, Tag, Trash2, Users,
} from 'lucide-react-native';
import { Button, Card, FilterChip, PressableScale } from '../../ui';
import { colors, radius, spacing, typography } from '../../theme/tokens';
import { formatIdr } from '../../utils/format';

export const TABS = [
  { key: 'group', label: 'Group', Icon: Users, types: ['group'] },
  { key: 'private', label: 'Private', Icon: Users, types: ['private', 'private_jamaah'] },
];

export const TAB_META = {
  group: {
    title: 'Layanan Group',
    subtitle: 'Untuk jemaah bertipe rombongan (group)',
    hotelHint: 'Harga hotel & transport di bawah hanya dipakai untuk booking group.',
  },
  private: {
    title: 'Layanan Private',
    subtitle: 'Untuk jemaah privat / keluarga',
    hotelHint: 'Harga hotel & transport di bawah hanya dipakai untuk booking private.',
  },
};

export function formatRupiahInput(value) {
  const digits = String(value ?? '').replace(/\D/g, '');
  if (!digits) return '';
  return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

export function parseRupiahInput(value) {
  return Number(String(value ?? '').replace(/\D/g, ''));
}

export function draftFromService(service) {
  return {
    name: service.name || '',
    description: service.description || '',
    daily_price: service.daily_price ? formatRupiahInput(String(Math.round(service.daily_price))) : '',
    same_hotel_price_per_day: service.same_hotel_price_per_day
      ? formatRupiahInput(String(Math.round(service.same_hotel_price_per_day)))
      : '',
    transport_price_flat: service.transport_price_flat
      ? formatRupiahInput(String(Math.round(service.transport_price_flat)))
      : '',
    min_pilgrims: String(service.min_pilgrims ?? ''),
    max_pilgrims: String(service.max_pilgrims ?? ''),
    add_ons: (service.add_ons || []).map((a) => ({
      name: a.name || '',
      price: a.price ? formatRupiahInput(String(Math.round(a.price))) : '',
    })),
  };
}

export function isPrivateType(type) {
  return type === 'private' || type === 'private_jamaah';
}

function FieldLabel({ children, hint }) {
  return (
    <View style={styles.fieldLabelWrap}>
      <Text style={styles.fieldLabel}>{children}</Text>
      {hint ? <Text style={styles.fieldHint}>{hint}</Text> : null}
    </View>
  );
}

function CurrencyInput({ value, onChangeText, placeholder }) {
  return (
    <View style={styles.currencyField}>
      <Text style={styles.currencyPrefix}>Rp</Text>
      <TextInput
        style={styles.currencyInput}
        keyboardType="numeric"
        value={value}
        onChangeText={(v) => onChangeText(formatRupiahInput(v))}
        placeholder={placeholder || '0'}
        placeholderTextColor={colors.textMuted}
      />
    </View>
  );
}

export function ServiceSummary({ service }) {
  const daily = parseRupiahInput(service.daily_price);
  const addOnCount = (service.add_ons || []).length;
  const isComplete = daily > 0 && service.min_pilgrims > 0 && service.max_pilgrims >= service.min_pilgrims;

  return (
    <Card style={styles.summaryCard} padding={spacing.lg} elevated={false}>
      <View style={styles.summaryTop}>
        <View style={styles.summaryIcon}>
          <Tag size={18} color={colors.baytgo} strokeWidth={2} />
        </View>
        <View style={styles.summaryCopy}>
          <Text style={styles.summaryTitle}>{service.name || 'Belum diatur'}</Text>
          <Text style={styles.summaryPrice}>{daily > 0 ? `${formatIdr(daily)} / hari` : 'Harga belum diisi'}</Text>
        </View>
        <View style={[styles.statusChip, isComplete ? styles.statusChipOk : styles.statusChipWarn]}>
          <Text style={[styles.statusChipText, isComplete ? styles.statusChipTextOk : styles.statusChipTextWarn]}>
            {isComplete ? 'Siap' : 'Lengkapi'}
          </Text>
        </View>
      </View>
      <View style={styles.summaryStats}>
        <View style={styles.summaryStat}>
          <Users size={14} color={colors.textSecondary} strokeWidth={2} />
          <Text style={styles.summaryStatText}>
            {service.min_pilgrims || '—'}–{service.max_pilgrims || '—'} jamaah
          </Text>
        </View>
        {isPrivateType(service.type) ? (
          <View style={styles.summaryStat}>
            <CirclePlus size={14} color={colors.textSecondary} strokeWidth={2} />
            <Text style={styles.summaryStatText}>{addOnCount} add-on</Text>
          </View>
        ) : null}
      </View>
    </Card>
  );
}

export function ServiceForm({ tabKey, service, draft, onDraftChange, onSave, saving }) {
  const meta = TAB_META[tabKey];
  const showAddOns = isPrivateType(service.type);

  return (
    <Card style={styles.formCard} padding={spacing.xl} elevated={false}>
      <View style={styles.formHead}>
        <Text style={styles.formTitle}>{meta.title}</Text>
        <Text style={styles.formSub}>{meta.subtitle}</Text>
      </View>

      <FieldLabel>Nama layanan</FieldLabel>
      <TextInput
        style={styles.input}
        value={draft.name}
        onChangeText={(v) => onDraftChange({ ...draft, name: v })}
        placeholder="Contoh: Layanan Umrah Eksekutif 9 Hari"
        placeholderTextColor={colors.textMuted}
      />

      <FieldLabel>Harga harian</FieldLabel>
      <CurrencyInput
        value={draft.daily_price}
        onChangeText={(v) => onDraftChange({ ...draft, daily_price: v })}
        placeholder="250.000"
      />

      <View style={styles.pilgrimRow}>
        <View style={styles.pilgrimField}>
          <FieldLabel>Min jamaah</FieldLabel>
          <TextInput
            style={styles.input}
            keyboardType="number-pad"
            value={draft.min_pilgrims}
            onChangeText={(v) => onDraftChange({ ...draft, min_pilgrims: v.replace(/\D/g, '') })}
            placeholder="10"
            placeholderTextColor={colors.textMuted}
          />
        </View>
        <View style={styles.pilgrimField}>
          <FieldLabel>Max jamaah</FieldLabel>
          <TextInput
            style={styles.input}
            keyboardType="number-pad"
            value={draft.max_pilgrims}
            onChangeText={(v) => onDraftChange({ ...draft, max_pilgrims: v.replace(/\D/g, '') })}
            placeholder="20"
            placeholderTextColor={colors.textMuted}
          />
        </View>
      </View>

      <FieldLabel>Deskripsi</FieldLabel>
      <TextInput
        style={[styles.input, styles.textarea]}
        value={draft.description}
        onChangeText={(v) => onDraftChange({ ...draft, description: v })}
        placeholder="Jelaskan layanan, fasilitas, pendampingan, dll."
        placeholderTextColor={colors.textMuted}
        multiline
        textAlignVertical="top"
      />

      <View style={styles.sectionDivider}>
        <Info size={16} color={colors.textSecondary} strokeWidth={2} />
        <Text style={styles.sectionHint}>{meta.hotelHint}</Text>
      </View>

      <FieldLabel>Harga hotel sama / hari</FieldLabel>
      <CurrencyInput
        value={draft.same_hotel_price_per_day}
        onChangeText={(v) => onDraftChange({ ...draft, same_hotel_price_per_day: v })}
        placeholder="100.000"
      />

      <FieldLabel>Harga transportasi (flat)</FieldLabel>
      <CurrencyInput
        value={draft.transport_price_flat}
        onChangeText={(v) => onDraftChange({ ...draft, transport_price_flat: v })}
        placeholder="300.000"
      />

      {showAddOns ? (
        <View style={styles.addOnSection}>
          <FieldLabel hint="Opsi tambahan untuk layanan private beserta harganya">
            Add-on layanan
          </FieldLabel>
          {(draft.add_ons || []).map((addon, index) => (
            <View key={`addon-${index}`} style={styles.addOnRow}>
              <TextInput
                style={[styles.input, styles.addOnName]}
                placeholder="Nama add-on"
                placeholderTextColor={colors.textMuted}
                value={addon.name}
                onChangeText={(v) => {
                  const next = [...(draft.add_ons || [])];
                  next[index] = { ...next[index], name: v };
                  onDraftChange({ ...draft, add_ons: next });
                }}
              />
              <View style={styles.addOnPriceWrap}>
                <CurrencyInput
                  value={addon.price}
                  onChangeText={(v) => {
                    const next = [...(draft.add_ons || [])];
                    next[index] = { ...next[index], price: v };
                    onDraftChange({ ...draft, add_ons: next });
                  }}
                  placeholder="0"
                />
              </View>
              <PressableScale
                onPress={() => {
                  const next = (draft.add_ons || []).filter((_, i) => i !== index);
                  onDraftChange({ ...draft, add_ons: next });
                }}
                haptic="light"
                style={styles.addOnRemove}
              >
                <Trash2 size={18} color={colors.error} strokeWidth={2} />
              </PressableScale>
            </View>
          ))}
          <PressableScale
            onPress={() => onDraftChange({ ...draft, add_ons: [...(draft.add_ons || []), { name: '', price: '' }] })}
            haptic="light"
            style={styles.addOnBtn}
          >
            <CirclePlus size={18} color={colors.baytgo} strokeWidth={2} />
            <Text style={styles.addOnBtnText}>Tambah add-on</Text>
          </PressableScale>
        </View>
      ) : null}

      <Button
        label={`Simpan ${tabKey === 'group' ? 'Layanan Group' : 'Layanan Private'}`}
        onPress={onSave}
        loading={saving}
        icon={<CheckCircle2 size={18} color={colors.white} strokeWidth={2} />}
        style={styles.submitBtn}
      />
    </Card>
  );
}

const styles = StyleSheet.create({
  summaryCard: { marginBottom: spacing.md },
  summaryTop: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  summaryIcon: {
    width: 42,
    height: 42,
    borderRadius: radius.sm,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  summaryCopy: { flex: 1 },
  summaryTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  summaryPrice: { marginTop: 3, ...typography.small, color: colors.textSecondary, fontWeight: '600' },
  statusChip: { paddingHorizontal: spacing.md, paddingVertical: 5, borderRadius: radius.full },
  statusChipOk: { backgroundColor: '#DCFCE7' },
  statusChipWarn: { backgroundColor: '#FEF3C7' },
  statusChipText: { ...typography.small, fontSize: 11 },
  statusChipTextOk: { color: '#166534' },
  statusChipTextWarn: { color: '#92400E' },
  summaryStats: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  summaryStat: { flexDirection: 'row', alignItems: 'center', gap: 5 },
  summaryStatText: { ...typography.small, color: colors.textSecondary, fontWeight: '500' },
  formCard: { marginBottom: spacing.lg },
  formHead: {
    marginBottom: spacing.lg,
    paddingBottom: spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  formTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  formSub: { marginTop: spacing.xs, ...typography.small, color: colors.textSecondary, lineHeight: 17, fontWeight: '500' },
  fieldLabelWrap: { marginBottom: spacing.sm },
  fieldLabel: { ...typography.label, color: colors.textSecondary },
  fieldHint: { marginTop: 2, ...typography.small, fontSize: 11, color: colors.textMuted, lineHeight: 15, fontWeight: '500' },
  input: {
    backgroundColor: colors.background,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md + 1,
    marginBottom: spacing.md,
    ...typography.caption,
    color: colors.textPrimary,
    borderWidth: 1,
    borderColor: colors.border,
  },
  currencyField: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.background,
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: spacing.lg,
    marginBottom: spacing.md,
  },
  currencyPrefix: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.textSecondary, marginRight: spacing.sm },
  currencyInput: {
    flex: 1,
    paddingVertical: spacing.md + 1,
    ...typography.caption,
    color: colors.textPrimary,
  },
  pilgrimRow: { flexDirection: 'row', gap: spacing.md },
  pilgrimField: { flex: 1 },
  textarea: { minHeight: 100, textAlignVertical: 'top' },
  sectionDivider: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    backgroundColor: colors.background,
    borderRadius: radius.sm,
    padding: spacing.md,
    marginBottom: spacing.md,
  },
  sectionHint: { flex: 1, ...typography.small, lineHeight: 17, color: colors.textSecondary, fontWeight: '500' },
  addOnSection: { marginTop: spacing.xs },
  addOnRow: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.sm, marginBottom: spacing.xs },
  addOnName: { flex: 1, marginBottom: 0 },
  addOnPriceWrap: { width: 120 },
  addOnRemove: {
    width: 40,
    height: 48,
    alignItems: 'center',
    justifyContent: 'center',
  },
  addOnBtn: { flexDirection: 'row', alignItems: 'center', gap: 6, alignSelf: 'flex-start', marginTop: spacing.xs, marginBottom: spacing.sm, paddingVertical: 6 },
  addOnBtnText: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  submitBtn: { marginTop: spacing.sm },
});
