import React, { useCallback, useState } from 'react';
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
  Platform,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import TabPageHeader from '../components/TabPageHeader';
import { fetchServices, updateService } from '../api/services';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';
import { formatIdr } from '../utils/format';

function ServiceRow({ service, expanded, onToggle, draft, onDraftChange, onSave, saving }) {
  return (
    <View style={styles.card}>
      <TouchableOpacity style={styles.cardHeader} onPress={onToggle} activeOpacity={0.9}>
        <View style={styles.cardMeta}>
          <Text style={styles.cardTitle}>{service.name}</Text>
          <Text style={styles.cardPrice}>{formatIdr(service.daily_price)} / hari</Text>
          <Text style={styles.cardPilgrims}>
            Jamaah {service.min_pilgrims}–{service.max_pilgrims}
          </Text>
        </View>
        <Ionicons
          name={expanded ? 'chevron-up' : 'chevron-down'}
          size={18}
          color={colors.slate400}
        />
      </TouchableOpacity>

      {expanded ? (
        <View style={styles.editForm}>
          <Text style={styles.fieldLabel}>Nama layanan</Text>
          <TextInput
            style={styles.input}
            value={draft.name}
            onChangeText={(v) => onDraftChange({ ...draft, name: v })}
            placeholder="Contoh: Layanan Umrah Eksekutif"
          />
          <Text style={styles.fieldLabel}>Deskripsi</Text>
          <TextInput
            style={[styles.input, styles.textarea]}
            value={draft.description}
            onChangeText={(v) => onDraftChange({ ...draft, description: v })}
            placeholder="Jelaskan layanan, fasilitas, pendampingan, dll."
            multiline
          />
          <Text style={styles.fieldLabel}>Harga harian</Text>
          <TextInput
            style={styles.input}
            keyboardType="numeric"
            value={draft.daily_price}
            onChangeText={(v) => onDraftChange({ ...draft, daily_price: v })}
          />
          <Text style={styles.fieldLabel}>Harga hotel sama / hari</Text>
          <TextInput
            style={styles.input}
            keyboardType="numeric"
            value={draft.same_hotel_price_per_day}
            onChangeText={(v) => onDraftChange({ ...draft, same_hotel_price_per_day: v })}
          />
          <Text style={styles.fieldLabel}>Transport flat</Text>
          <TextInput
            style={styles.input}
            keyboardType="numeric"
            value={draft.transport_price_flat}
            onChangeText={(v) => onDraftChange({ ...draft, transport_price_flat: v })}
          />
          <View style={styles.pilgrimRow}>
            <View style={styles.pilgrimField}>
              <Text style={styles.fieldLabel}>Min jamaah</Text>
              <TextInput
                style={styles.input}
                keyboardType="number-pad"
                value={draft.min_pilgrims}
                onChangeText={(v) => onDraftChange({ ...draft, min_pilgrims: v })}
              />
            </View>
            <View style={styles.pilgrimField}>
              <Text style={styles.fieldLabel}>Max jamaah</Text>
              <TextInput
                style={styles.input}
                keyboardType="number-pad"
                value={draft.max_pilgrims}
                onChangeText={(v) => onDraftChange({ ...draft, max_pilgrims: v })}
              />
            </View>
          </View>

          {(service.type === 'private' || service.type === 'private_jamaah') ? (
            <View style={styles.addOnSection}>
              <Text style={styles.fieldLabel}>Add-on layanan privat</Text>
              {(draft.add_ons || []).map((addon, index) => (
                <View key={`addon-${index}`} style={styles.addOnRow}>
                  <TextInput
                    style={[styles.input, styles.addOnName]}
                    placeholder="Nama add-on"
                    value={addon.name}
                    onChangeText={(v) => {
                      const next = [...(draft.add_ons || [])];
                      next[index] = { ...next[index], name: v };
                      onDraftChange({ ...draft, add_ons: next });
                    }}
                  />
                  <TextInput
                    style={[styles.input, styles.addOnPrice]}
                    placeholder="Harga"
                    keyboardType="numeric"
                    value={addon.price}
                    onChangeText={(v) => {
                      const next = [...(draft.add_ons || [])];
                      next[index] = { ...next[index], price: v };
                      onDraftChange({ ...draft, add_ons: next });
                    }}
                  />
                  <TouchableOpacity
                    onPress={() => {
                      const next = (draft.add_ons || []).filter((_, i) => i !== index);
                      onDraftChange({ ...draft, add_ons: next });
                    }}
                  >
                    <Ionicons name="trash-outline" size={18} color="#B91C1C" />
                  </TouchableOpacity>
                </View>
              ))}
              <TouchableOpacity
                style={styles.addOnBtn}
                onPress={() => onDraftChange({ ...draft, add_ons: [...(draft.add_ons || []), { name: '', price: '' }] })}
              >
                <Ionicons name="add-circle-outline" size={16} color={colors.baytgo} />
                <Text style={styles.addOnBtnText}>Tambah add-on</Text>
              </TouchableOpacity>
            </View>
          ) : null}

          <TouchableOpacity
            style={[styles.submitBtn, saving && styles.submitBtnDisabled]}
            onPress={onSave}
            disabled={saving}
          >
            <Text style={styles.submitBtnText}>{saving ? 'Menyimpan…' : 'Simpan layanan'}</Text>
          </TouchableOpacity>
        </View>
      ) : null}
    </View>
  );
}

function promptEditPrice(service, onSave) {
  if (Platform.OS === 'ios' && Alert.prompt) {
    Alert.prompt(
      service.name,
      'Harga harian (Rp)',
      [
        { text: 'Batal', style: 'cancel' },
        {
          text: 'Simpan',
          onPress: (value) => {
            const daily = Number(String(value || '').replace(/\D/g, ''));
            if (!daily) {
              Alert.alert('Validasi', 'Masukkan harga yang valid.');
              return;
            }
            onSave({
              daily_price: String(daily),
              same_hotel_price_per_day: String(service.same_hotel_price_per_day),
              transport_price_flat: String(service.transport_price_flat),
              min_pilgrims: String(service.min_pilgrims),
              max_pilgrims: String(service.max_pilgrims),
            });
          },
        },
      ],
      'plain-text',
      String(service.daily_price),
      'numeric',
    );
    return true;
  }
  return false;
}

export default function ServicesScreen() {
  const { token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [services, setServices] = useState([]);
  const [expandedId, setExpandedId] = useState(null);
  const [drafts, setDrafts] = useState({});
  const [savingId, setSavingId] = useState(null);

  const load = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchServices(token);
      const list = data.services || [];
      setServices(list);
      const nextDrafts = {};
      list.forEach((s) => {
        nextDrafts[s.id] = {
          name: s.name || '',
          description: s.description || '',
          daily_price: String(s.daily_price ?? ''),
          same_hotel_price_per_day: String(s.same_hotel_price_per_day ?? ''),
          transport_price_flat: String(s.transport_price_flat ?? ''),
          min_pilgrims: String(s.min_pilgrims ?? ''),
          max_pilgrims: String(s.max_pilgrims ?? ''),
          add_ons: (s.add_ons || []).map((a) => ({ name: a.name || '', price: String(a.price ?? '') })),
        };
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

  const saveService = async (service, draft) => {
    const name = draft.name?.trim();
    if (!name) {
      Alert.alert('Validasi', 'Nama layanan wajib diisi.');
      return;
    }

    const daily = Number(String(draft.daily_price).replace(/\D/g, ''));
    const sameHotel = Number(String(draft.same_hotel_price_per_day).replace(/\D/g, ''));
    const transport = Number(String(draft.transport_price_flat).replace(/\D/g, ''));
    const minP = parseInt(draft.min_pilgrims, 10);
    const maxP = parseInt(draft.max_pilgrims, 10);

    if (!daily || minP < 1 || maxP < minP) {
      Alert.alert('Validasi', 'Periksa harga dan jumlah jamaah.');
      return;
    }

    setSavingId(service.id);
    try {
      await updateService(token, service.id, {
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
            price: Number(String(a.price).replace(/\D/g, '')) || 0,
          })),
      });
      Alert.alert('Berhasil', 'Layanan diperbarui.');
      setExpandedId(null);
      await load(true);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat menyimpan layanan');
    } finally {
      setSavingId(null);
    }
  };

  const handleToggle = (service) => {
    if (expandedId === service.id) {
      setExpandedId(null);
      return;
    }

    const usedPrompt = promptEditPrice(service, (draft) => saveService(service, draft));
    if (!usedPrompt) {
      setExpandedId(service.id);
    }
  };

  return (
    <View style={styles.container}>
      <TabPageHeader title="Layanan" subtitle="Atur nama, deskripsi, harga & kapasitas" />

      {loading && !refreshing ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <ScrollView
          contentContainerStyle={styles.scroll}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />
          }
        >
          {services.length === 0 ? (
            <Text style={styles.muted}>Belum ada layanan terdaftar.</Text>
          ) : (
            services.map((service) => (
              <ServiceRow
                key={String(service.id)}
                service={service}
                expanded={expandedId === service.id}
                draft={drafts[service.id] || {}}
                onDraftChange={(d) => setDrafts((prev) => ({ ...prev, [service.id]: d }))}
                onToggle={() => handleToggle(service)}
                onSave={() => saveService(service, drafts[service.id])}
                saving={savingId === service.id}
              />
            ))
          )}
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { paddingHorizontal: 20, paddingBottom: 32 },
  loader: { marginTop: 40 },
  muted: { fontSize: 14, color: colors.slate500, fontWeight: '600' },
  card: {
    backgroundColor: colors.white,
    borderRadius: 16,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: colors.slate100,
    overflow: 'hidden',
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    gap: 12,
  },
  cardMeta: { flex: 1 },
  cardTitle: { fontSize: 15, fontWeight: '900', color: colors.baytgo },
  cardPrice: { marginTop: 4, fontSize: 13, fontWeight: '700', color: colors.slate700 },
  cardPilgrims: { marginTop: 2, fontSize: 11, fontWeight: '600', color: colors.slate500 },
  editForm: {
    borderTopWidth: 1,
    borderTopColor: colors.slate100,
    padding: 16,
    paddingTop: 12,
  },
  fieldLabel: { fontSize: 11, fontWeight: '700', color: colors.slate500, marginBottom: 6 },
  input: {
    backgroundColor: colors.canvas,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    marginBottom: 10,
    fontSize: 14,
    fontWeight: '600',
    color: colors.slate900,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  pilgrimRow: { flexDirection: 'row', gap: 10 },
  pilgrimField: { flex: 1 },
  textarea: { minHeight: 88, textAlignVertical: 'top' },
  submitBtn: {
    marginTop: 4,
    backgroundColor: colors.baytgo,
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
  },
  submitBtnDisabled: { opacity: 0.6 },
  submitBtnText: { color: colors.white, fontWeight: '800', fontSize: 14 },
  addOnSection: { marginTop: 4, marginBottom: 8 },
  addOnRow: { flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: 8 },
  addOnName: { flex: 1, marginBottom: 0 },
  addOnPrice: { width: 100, marginBottom: 0 },
  addOnBtn: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  addOnBtnText: { fontSize: 12, fontWeight: '800', color: colors.baytgo },
});
