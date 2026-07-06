import React, { useCallback, useMemo, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  ActivityIndicator,
  RefreshControl,
  TouchableOpacity,
  TextInput,
  Alert,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import TabPageHeader from '../components/TabPageHeader';
import { fetchServices, updateService } from '../api/services';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';
import { formatIdr } from '../utils/format';

const TABS = [
  { key: 'group', label: 'Group', icon: 'people-outline', types: ['group'] },
  { key: 'private', label: 'Private', icon: 'person-outline', types: ['private', 'private_jamaah'] },
];

const TAB_META = {
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

function formatRupiahInput(value) {
  const digits = String(value ?? '').replace(/\D/g, '');
  if (!digits) return '';
  return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function parseRupiahInput(value) {
  return Number(String(value ?? '').replace(/\D/g, ''));
}

function draftFromService(service) {
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

function isPrivateType(type) {
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
        placeholderTextColor={colors.slate400}
      />
    </View>
  );
}

function ServiceSummary({ service }) {
  const daily = parseRupiahInput(service.daily_price);
  const addOnCount = (service.add_ons || []).length;
  const isComplete = daily > 0 && service.min_pilgrims > 0 && service.max_pilgrims >= service.min_pilgrims;

  return (
    <View style={styles.summaryCard}>
      <View style={styles.summaryTop}>
        <View style={styles.summaryIcon}>
          <Ionicons name="pricetag" size={18} color={colors.baytgo} />
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
          <Ionicons name="people-outline" size={14} color={colors.slate500} />
          <Text style={styles.summaryStatText}>
            {service.min_pilgrims || '—'}–{service.max_pilgrims || '—'} jamaah
          </Text>
        </View>
        {isPrivateType(service.type) ? (
          <View style={styles.summaryStat}>
            <Ionicons name="add-circle-outline" size={14} color={colors.slate500} />
            <Text style={styles.summaryStatText}>{addOnCount} add-on</Text>
          </View>
        ) : null}
      </View>
    </View>
  );
}

function ServiceForm({ tabKey, service, draft, onDraftChange, onSave, saving }) {
  const meta = TAB_META[tabKey];
  const showAddOns = isPrivateType(service.type);

  return (
    <View style={styles.formCard}>
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
        placeholderTextColor={colors.slate400}
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
            placeholderTextColor={colors.slate400}
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
            placeholderTextColor={colors.slate400}
          />
        </View>
      </View>

      <FieldLabel>Deskripsi</FieldLabel>
      <TextInput
        style={[styles.input, styles.textarea]}
        value={draft.description}
        onChangeText={(v) => onDraftChange({ ...draft, description: v })}
        placeholder="Jelaskan layanan, fasilitas, pendampingan, dll."
        placeholderTextColor={colors.slate400}
        multiline
        textAlignVertical="top"
      />

      <View style={styles.sectionDivider}>
        <Ionicons name="information-circle-outline" size={16} color={colors.slate500} />
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
                placeholderTextColor={colors.slate400}
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
              <TouchableOpacity
                style={styles.addOnRemove}
                onPress={() => {
                  const next = (draft.add_ons || []).filter((_, i) => i !== index);
                  onDraftChange({ ...draft, add_ons: next });
                }}
                hitSlop={8}
              >
                <Ionicons name="trash-outline" size={18} color="#B91C1C" />
              </TouchableOpacity>
            </View>
          ))}
          <TouchableOpacity
            style={styles.addOnBtn}
            onPress={() => onDraftChange({ ...draft, add_ons: [...(draft.add_ons || []), { name: '', price: '' }] })}
          >
            <Ionicons name="add-circle-outline" size={18} color={colors.baytgo} />
            <Text style={styles.addOnBtnText}>Tambah add-on</Text>
          </TouchableOpacity>
        </View>
      ) : null}

      <TouchableOpacity style={styles.submitBtn} onPress={onSave} disabled={saving} activeOpacity={0.9}>
        <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.submitGradient}>
          {saving ? (
            <ActivityIndicator color={colors.white} size="small" />
          ) : (
            <>
              <Ionicons name="checkmark-circle-outline" size={18} color={colors.white} />
              <Text style={styles.submitBtnText}>
                Simpan {tabKey === 'group' ? 'Layanan Group' : 'Layanan Private'}
              </Text>
            </>
          )}
        </LinearGradient>
      </TouchableOpacity>
    </View>
  );
}

export default function ServicesScreen() {
  const { token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [services, setServices] = useState([]);
  const [activeTab, setActiveTab] = useState('group');
  const [drafts, setDrafts] = useState({});
  const [saving, setSaving] = useState(false);

  const load = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchServices(token);
      const list = data.services || [];
      setServices(list);
      const nextDrafts = {};
      list.forEach((s) => {
        nextDrafts[s.id] = draftFromService(s);
      });
      setDrafts(nextDrafts);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat memuat layanan');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token]);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

  const activeService = useMemo(() => {
    const tab = TABS.find((t) => t.key === activeTab);
    if (!tab) return null;
    return services.find((s) => tab.types.includes(s.type)) || null;
  }, [services, activeTab]);

  const saveService = async () => {
    if (!activeService) return;
    const draft = drafts[activeService.id] || {};
    const name = draft.name?.trim();
    if (!name) {
      Alert.alert('Validasi', 'Nama layanan wajib diisi.');
      return;
    }

    const daily = parseRupiahInput(draft.daily_price);
    const sameHotel = parseRupiahInput(draft.same_hotel_price_per_day);
    const transport = parseRupiahInput(draft.transport_price_flat);
    const minP = parseInt(draft.min_pilgrims, 10);
    const maxP = parseInt(draft.max_pilgrims, 10);

    if (!daily || minP < 1 || maxP < minP) {
      Alert.alert('Validasi', 'Periksa harga harian dan jumlah jamaah.');
      return;
    }

    setSaving(true);
    try {
      await updateService(token, activeService.id, {
        name,
        description: draft.description?.trim() || '',
        daily_price: daily,
        same_hotel_price_per_day: sameHotel,
        transport_price_flat: transport,
        min_pilgrims: minP,
        max_pilgrims: maxP,
        add_ons: (draft.add_ons || [])
          .filter((a) => a.name?.trim())
          .map((a) => ({
            name: a.name.trim(),
            price: parseRupiahInput(a.price) || 0,
          })),
      });
      Alert.alert('Berhasil', 'Layanan diperbarui.');
      await load(true);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat menyimpan layanan');
    } finally {
      setSaving(false);
    }
  };

  return (
    <View style={styles.container}>
      <TabPageHeader title="Layanan" subtitle="Atur layanan group & private untuk jamaah" />

      {loading && !refreshing ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <KeyboardAvoidingView
          style={styles.flex}
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        >
          <ScrollView
            contentContainerStyle={styles.scroll}
            showsVerticalScrollIndicator={false}
            keyboardShouldPersistTaps="handled"
            refreshControl={
              <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />
            }
          >
            <View style={styles.infoBanner}>
              <Ionicons name="bulb-outline" size={18} color={colors.baytgo} />
              <Text style={styles.infoText}>
                Lengkapi kedua jenis layanan agar profil Anda siap menerima permintaan booking dari jamaah.
              </Text>
            </View>

            <View style={styles.tabBar}>
              {TABS.map((tab) => {
                const active = activeTab === tab.key;
                return (
                  <TouchableOpacity
                    key={tab.key}
                    style={[styles.tabBtn, active && styles.tabBtnActive]}
                    onPress={() => setActiveTab(tab.key)}
                    activeOpacity={0.88}
                  >
                    <Ionicons name={tab.icon} size={16} color={active ? colors.white : colors.slate600} />
                    <Text style={[styles.tabBtnText, active && styles.tabBtnTextActive]}>{tab.label}</Text>
                  </TouchableOpacity>
                );
              })}
            </View>

            {activeService ? (
              <>
                <ServiceSummary service={activeService} />
                <ServiceForm
                  tabKey={activeTab}
                  service={activeService}
                  draft={drafts[activeService.id] || {}}
                  onDraftChange={(d) => setDrafts((prev) => ({ ...prev, [activeService.id]: d }))}
                  onSave={saveService}
                  saving={saving}
                />
              </>
            ) : (
              <View style={styles.empty}>
                <View style={styles.emptyIcon}>
                  <Ionicons name="pricetag-outline" size={32} color={colors.slate400} />
                </View>
                <Text style={styles.emptyTitle}>Layanan belum tersedia</Text>
                <Text style={styles.emptyText}>
                  Hubungi admin jika layanan group atau private belum muncul di akun Anda.
                </Text>
              </View>
            )}
          </ScrollView>
        </KeyboardAvoidingView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  flex: { flex: 1 },
  scroll: { paddingHorizontal: 20, paddingTop: 16, paddingBottom: 40 },
  loader: { marginTop: 40 },
  infoBanner: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
    backgroundColor: colors.baytgoLight,
    borderRadius: 14,
    padding: 14,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.1)',
  },
  infoText: { flex: 1, fontSize: 13, lineHeight: 19, fontWeight: '600', color: colors.baytgo },
  tabBar: {
    flexDirection: 'row',
    gap: 10,
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 6,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  tabBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    paddingVertical: 12,
    borderRadius: 12,
    backgroundColor: colors.canvas,
  },
  tabBtnActive: { backgroundColor: colors.baytgo },
  tabBtnText: { fontSize: 14, fontWeight: '800', color: colors.slate600 },
  tabBtnTextActive: { color: colors.white },
  summaryCard: {
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 16,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.05,
    shadowRadius: 12,
    elevation: 2,
  },
  summaryTop: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  summaryIcon: {
    width: 42,
    height: 42,
    borderRadius: 12,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  summaryCopy: { flex: 1 },
  summaryTitle: { fontSize: 15, fontWeight: '900', color: colors.baytgo },
  summaryPrice: { marginTop: 3, fontSize: 13, fontWeight: '700', color: colors.slate600 },
  statusChip: {
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 999,
  },
  statusChipOk: { backgroundColor: '#DCFCE7' },
  statusChipWarn: { backgroundColor: '#FEF3C7' },
  statusChipText: { fontSize: 11, fontWeight: '800' },
  statusChipTextOk: { color: '#166534' },
  statusChipTextWarn: { color: '#92400E' },
  summaryStats: { flexDirection: 'row', flexWrap: 'wrap', gap: 14, marginTop: 12, paddingTop: 12, borderTopWidth: 1, borderTopColor: colors.slate100 },
  summaryStat: { flexDirection: 'row', alignItems: 'center', gap: 5 },
  summaryStatText: { fontSize: 12, fontWeight: '600', color: colors.slate500 },
  formCard: {
    backgroundColor: colors.white,
    borderRadius: 20,
    padding: 18,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.05,
    shadowRadius: 12,
    elevation: 2,
  },
  formHead: {
    marginBottom: 16,
    paddingBottom: 14,
    borderBottomWidth: 1,
    borderBottomColor: colors.slate100,
  },
  formTitle: { fontSize: 16, fontWeight: '900', color: colors.baytgo },
  formSub: { marginTop: 4, fontSize: 12, fontWeight: '600', color: colors.slate500, lineHeight: 17 },
  fieldLabelWrap: { marginBottom: 8 },
  fieldLabel: { fontSize: 12, fontWeight: '800', color: colors.slate600 },
  fieldHint: { marginTop: 2, fontSize: 11, fontWeight: '500', color: colors.slate400, lineHeight: 15 },
  input: {
    backgroundColor: colors.canvas,
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 13,
    marginBottom: 14,
    fontSize: 14,
    fontWeight: '600',
    color: colors.slate900,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  currencyField: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.canvas,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
    paddingHorizontal: 14,
    marginBottom: 14,
  },
  currencyPrefix: { fontSize: 14, fontWeight: '800', color: colors.slate500, marginRight: 8 },
  currencyInput: {
    flex: 1,
    paddingVertical: 13,
    fontSize: 14,
    fontWeight: '600',
    color: colors.slate900,
  },
  pilgrimRow: { flexDirection: 'row', gap: 10 },
  pilgrimField: { flex: 1 },
  textarea: { minHeight: 100, textAlignVertical: 'top' },
  sectionDivider: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 8,
    backgroundColor: colors.canvas,
    borderRadius: 12,
    padding: 12,
    marginBottom: 14,
    marginTop: 2,
  },
  sectionHint: { flex: 1, fontSize: 12, lineHeight: 17, fontWeight: '600', color: colors.slate500 },
  addOnSection: { marginTop: 4, marginBottom: 4 },
  addOnRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 8, marginBottom: 4 },
  addOnName: { flex: 1, marginBottom: 0 },
  addOnPriceWrap: { width: 120 },
  addOnRemove: {
    width: 40,
    height: 48,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 0,
  },
  addOnBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    alignSelf: 'flex-start',
    marginTop: 4,
    marginBottom: 8,
    paddingVertical: 6,
  },
  addOnBtnText: { fontSize: 13, fontWeight: '800', color: colors.baytgo },
  submitBtn: { marginTop: 8, borderRadius: 14, overflow: 'hidden' },
  submitGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 15,
  },
  submitBtnText: { color: colors.white, fontWeight: '800', fontSize: 14 },
  empty: { alignItems: 'center', paddingVertical: 48, paddingHorizontal: 20 },
  emptyIcon: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  emptyTitle: { fontSize: 16, fontWeight: '900', color: colors.baytgo },
  emptyText: { marginTop: 8, fontSize: 13, fontWeight: '600', color: colors.slate500, textAlign: 'center', lineHeight: 19 },
});
