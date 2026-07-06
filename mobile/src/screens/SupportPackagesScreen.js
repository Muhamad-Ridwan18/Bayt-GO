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
  Switch,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import TabPageHeader from '../components/TabPageHeader';
import { fetchSupportPackages, updateSupportPackages } from '../api/supportPackages';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

function emptyPackage(categories) {
  return {
    id: null,
    name: '',
    category: categories[0]?.value || 'other',
    description: '',
    price: '',
    min_pilgrims: '1',
    max_pilgrims: '1',
    is_active: true,
  };
}

function PackageRow({ item, categories, onChange, onRemove }) {
  return (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <Text style={styles.cardTitle}>{item.name || 'Paket baru'}</Text>
        <TouchableOpacity onPress={onRemove} hitSlop={8}>
          <Ionicons name="trash-outline" size={18} color="#B91C1C" />
        </TouchableOpacity>
      </View>

      <Text style={styles.label}>Nama paket</Text>
      <TextInput style={styles.input} value={item.name} onChangeText={(v) => onChange({ ...item, name: v })} />

      <Text style={styles.label}>Kategori</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips}>
        {categories.map((cat) => (
          <TouchableOpacity
            key={cat.value}
            style={[styles.chip, item.category === cat.value && styles.chipActive]}
            onPress={() => onChange({ ...item, category: cat.value })}
          >
            <Text style={[styles.chipText, item.category === cat.value && styles.chipTextActive]}>{cat.label}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <Text style={styles.label}>Harga (Rp)</Text>
      <TextInput style={styles.input} keyboardType="numeric" value={item.price} onChangeText={(v) => onChange({ ...item, price: v })} />

      <View style={styles.row2}>
        <View style={styles.half}>
          <Text style={styles.label}>Min jamaah</Text>
          <TextInput style={styles.input} keyboardType="number-pad" value={item.min_pilgrims} onChangeText={(v) => onChange({ ...item, min_pilgrims: v })} />
        </View>
        <View style={styles.half}>
          <Text style={styles.label}>Max jamaah</Text>
          <TextInput style={styles.input} keyboardType="number-pad" value={item.max_pilgrims} onChangeText={(v) => onChange({ ...item, max_pilgrims: v })} />
        </View>
      </View>

      <Text style={styles.label}>Deskripsi</Text>
      <TextInput
        style={[styles.input, styles.textarea]}
        value={item.description}
        onChangeText={(v) => onChange({ ...item, description: v })}
        multiline
      />

      <View style={styles.switchRow}>
        <Text style={styles.switchLabel}>Aktif</Text>
        <Switch
          value={item.is_active}
          onValueChange={(v) => onChange({ ...item, is_active: v })}
          trackColor={{ true: colors.baytgo }}
        />
      </View>
    </View>
  );
}

export default function SupportPackagesScreen() {
  const { token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [categories, setCategories] = useState([]);
  const [packages, setPackages] = useState([]);

  const load = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchSupportPackages(token);
      setCategories(data.categories || []);
      setPackages(
        (data.packages || []).map((p) => ({
          ...p,
          price: String(p.price ?? ''),
          min_pilgrims: String(p.min_pilgrims ?? 1),
          max_pilgrims: String(p.max_pilgrims ?? 1),
        })),
      );
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat memuat paket');
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

  const handleSave = async () => {
    setSaving(true);
    try {
      const payload = packages.map((p) => ({
        id: p.id || undefined,
        name: p.name.trim(),
        category: p.category,
        description: p.description?.trim() || null,
        price: Number(String(p.price).replace(/\D/g, '')),
        min_pilgrims: parseInt(p.min_pilgrims, 10) || 1,
        max_pilgrims: parseInt(p.max_pilgrims, 10) || 1,
        is_active: !!p.is_active,
      }));
      await updateSupportPackages(token, payload);
      Alert.alert('Berhasil', 'Paket layanan pendukung disimpan.');
      await load(true);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat menyimpan');
    } finally {
      setSaving(false);
    }
  };

  return (
    <View style={styles.container}>
      <TabPageHeader title="Layanan pendukung" subtitle="Kelola paket layanan Anda" />

      {loading && !refreshing ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <ScrollView
          contentContainerStyle={styles.scroll}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />}
        >
          {packages.map((pkg, index) => (
            <PackageRow
              key={pkg.id || `new-${index}`}
              item={pkg}
              categories={categories}
              onChange={(next) => setPackages((prev) => prev.map((p, i) => (i === index ? next : p)))}
              onRemove={() => setPackages((prev) => prev.filter((_, i) => i !== index))}
            />
          ))}

          <TouchableOpacity
            style={styles.addBtn}
            onPress={() => setPackages((prev) => [...prev, emptyPackage(categories)])}
          >
            <Ionicons name="add-circle-outline" size={20} color={colors.baytgo} />
            <Text style={styles.addBtnText}>Tambah paket</Text>
          </TouchableOpacity>

          <TouchableOpacity style={[styles.saveBtn, saving && styles.saveBtnDisabled]} onPress={handleSave} disabled={saving}>
            <Text style={styles.saveBtnText}>{saving ? 'Menyimpan…' : 'Simpan semua paket'}</Text>
          </TouchableOpacity>
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { paddingHorizontal: 20, paddingBottom: 32 },
  loader: { marginTop: 40 },
  card: {
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  cardTitle: { fontSize: 15, fontWeight: '900', color: colors.baytgo },
  label: { fontSize: 11, fontWeight: '700', color: colors.slate500, marginBottom: 6, marginTop: 4 },
  input: {
    backgroundColor: colors.canvas,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    marginBottom: 8,
    fontSize: 14,
    fontWeight: '600',
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  textarea: { minHeight: 72, textAlignVertical: 'top' },
  chips: { gap: 8, marginBottom: 8 },
  chip: {
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
    backgroundColor: colors.canvas,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  chipActive: { backgroundColor: colors.emerald50, borderColor: colors.baytgo },
  chipText: { fontSize: 11, fontWeight: '700', color: colors.slate600 },
  chipTextActive: { color: colors.baytgo },
  row2: { flexDirection: 'row', gap: 10 },
  half: { flex: 1 },
  switchRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 4 },
  switchLabel: { fontSize: 13, fontWeight: '700', color: colors.slate700 },
  addBtn: { flexDirection: 'row', alignItems: 'center', gap: 8, marginVertical: 12 },
  addBtnText: { fontSize: 14, fontWeight: '800', color: colors.baytgo },
  saveBtn: {
    backgroundColor: colors.baytgo,
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
  },
  saveBtnDisabled: { opacity: 0.6 },
  saveBtnText: { color: colors.white, fontWeight: '800', fontSize: 14 },
});
