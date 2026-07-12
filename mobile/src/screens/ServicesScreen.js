import React, { useCallback, useMemo, useState } from 'react';
import {
  View, Text, StyleSheet, ScrollView, RefreshControl, Alert, KeyboardAvoidingView, Platform,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { Lightbulb } from 'lucide-react-native';
import TabPageHeader from '../components/TabPageHeader';
import { fetchServices, updateService } from '../api/services';
import { useAuth } from '../context/AuthContext';
import { Card, EmptyState, FilterChip, SkeletonList } from '../ui';
import {
  TABS, draftFromService, isPrivateType, parseRupiahInput, ServiceForm, ServiceSummary,
} from '../features/services/ServiceScreenParts';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { notifyError, notifySuccess } from '../utils/feedback';

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
      list.forEach((s) => { nextDrafts[s.id] = draftFromService(s); });
      setDrafts(nextDrafts);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat memuat layanan');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token]);

  useFocusEffect(useCallback(() => { load(); }, [load]));

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
          .map((a) => ({ name: a.name.trim(), price: parseRupiahInput(a.price) || 0 })),
      });
      notifySuccess('Layanan diperbarui.');
      await load(true);
    } catch (err) {
      notifyError(err.message || 'Tidak dapat menyimpan layanan');
    } finally {
      setSaving(false);
    }
  };

  return (
    <View style={styles.container}>
      <TabPageHeader title="Layanan" subtitle="Atur layanan group & private untuk jamaah" />

      {loading && !refreshing ? (
        <SkeletonList count={3} style={styles.skeleton} />
      ) : (
        <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
          <ScrollView
            contentContainerStyle={styles.scroll}
            showsVerticalScrollIndicator={false}
            keyboardShouldPersistTaps="handled"
            refreshControl={
              <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />
            }
          >
            <Card style={styles.infoBanner} padding={spacing.lg} elevated={false}>
              <Lightbulb size={18} color={colors.baytgo} strokeWidth={2} />
              <Text style={styles.infoText}>
                Lengkapi kedua jenis layanan agar profil Anda siap menerima permintaan booking dari jamaah.
              </Text>
            </Card>

            <View style={styles.tabBar}>
              {TABS.map((tab) => (
                <FilterChip
                  key={tab.key}
                  label={tab.label}
                  icon={tab.Icon}
                  active={activeTab === tab.key}
                  onPress={() => setActiveTab(tab.key)}
                />
              ))}
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
              <EmptyState
                variant="package"
                title="Layanan belum tersedia"
                description="Hubungi admin jika layanan group atau private belum muncul di akun Anda."
              />
            )}
          </ScrollView>
        </KeyboardAvoidingView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  flex: { flex: 1 },
  skeleton: { paddingHorizontal: layout.screenPadding, paddingTop: spacing.lg },
  scroll: { paddingHorizontal: layout.screenPadding, paddingTop: spacing.lg, paddingBottom: spacing['4xl'] },
  infoBanner: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.md,
    backgroundColor: colors.baytgoLight,
    borderColor: 'rgba(26,61,52,0.1)',
    marginBottom: spacing.lg,
  },
  infoText: { flex: 1, ...typography.caption, lineHeight: 20, fontWeight: '600', color: colors.baytgo },
  tabBar: { flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.lg },
});
