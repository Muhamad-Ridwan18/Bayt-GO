import React, { useCallback, useState } from 'react';
import {
  View, Text, StyleSheet, ScrollView, RefreshControl, TextInput, Alert, Switch,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { CirclePlus, Trash2 } from 'lucide-react-native';
import TabPageHeader from '../components/TabPageHeader';
import { fetchSupportPackages, updateSupportPackages } from '../api/supportPackages';
import { useAuth } from '../context/AuthContext';
import { Button, Card, FilterChip, PressableScale, SkeletonList } from '../ui';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { notifyError, notifySuccess } from '../utils/feedback';

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
    <Card style={styles.card} padding={spacing.lg} elevated={false}>
      <View style={styles.cardHeader}>
        <Text style={styles.cardTitle}>{item.name || 'Paket baru'}</Text>
        <PressableScale onPress={onRemove} haptic="light">
          <Trash2 size={18} color={colors.error} strokeWidth={2} />
        </PressableScale>
      </View>

      <Text style={styles.label}>Nama paket</Text>
      <TextInput style={styles.input} value={item.name} onChangeText={(v) => onChange({ ...item, name: v })} placeholderTextColor={colors.textMuted} />

      <Text style={styles.label}>Kategori</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips}>
        {categories.map((cat) => (
          <FilterChip
            key={cat.value}
            label={cat.label}
            active={item.category === cat.value}
            onPress={() => onChange({ ...item, category: cat.value })}
          />
        ))}
      </ScrollView>

      <Text style={styles.label}>Harga (Rp)</Text>
      <TextInput style={styles.input} keyboardType="numeric" value={item.price} onChangeText={(v) => onChange({ ...item, price: v })} placeholderTextColor={colors.textMuted} />

      <View style={styles.row2}>
        <View style={styles.half}>
          <Text style={styles.label}>Min jamaah</Text>
          <TextInput style={styles.input} keyboardType="number-pad" value={item.min_pilgrims} onChangeText={(v) => onChange({ ...item, min_pilgrims: v })} placeholderTextColor={colors.textMuted} />
        </View>
        <View style={styles.half}>
          <Text style={styles.label}>Max jamaah</Text>
          <TextInput style={styles.input} keyboardType="number-pad" value={item.max_pilgrims} onChangeText={(v) => onChange({ ...item, max_pilgrims: v })} placeholderTextColor={colors.textMuted} />
        </View>
      </View>

      <Text style={styles.label}>Deskripsi</Text>
      <TextInput
        style={[styles.input, styles.textarea]}
        value={item.description}
        onChangeText={(v) => onChange({ ...item, description: v })}
        multiline
        placeholderTextColor={colors.textMuted}
      />

      <View style={styles.switchRow}>
        <Text style={styles.switchLabel}>Aktif</Text>
        <Switch value={item.is_active} onValueChange={(v) => onChange({ ...item, is_active: v })} trackColor={{ true: colors.baytgo }} />
      </View>
    </Card>
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

  useFocusEffect(useCallback(() => { load(); }, [load]));

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
      notifySuccess('Paket layanan pendukung disimpan.');
      await load(true);
    } catch (err) {
      notifyError(err.message || 'Tidak dapat menyimpan');
    } finally {
      setSaving(false);
    }
  };

  return (
    <View style={styles.container}>
      <TabPageHeader title="Layanan pendukung" subtitle="Kelola paket layanan Anda" />

      {loading && !refreshing ? (
        <SkeletonList count={3} style={styles.skeleton} />
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

          <PressableScale
            onPress={() => setPackages((prev) => [...prev, emptyPackage(categories)])}
            haptic="light"
            style={styles.addBtn}
          >
            <CirclePlus size={20} color={colors.baytgo} strokeWidth={2} />
            <Text style={styles.addBtnText}>Tambah paket</Text>
          </PressableScale>

          <Button label="Simpan semua paket" onPress={handleSave} loading={saving} />
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  skeleton: { paddingHorizontal: layout.screenPadding, paddingTop: spacing.lg },
  scroll: { paddingHorizontal: layout.screenPadding, paddingBottom: spacing['3xl'] },
  card: { marginBottom: spacing.md },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: spacing.md },
  cardTitle: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
  label: { ...typography.label, color: colors.textSecondary, marginBottom: 6, marginTop: spacing.xs },
  input: {
    backgroundColor: colors.background,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    marginBottom: spacing.sm,
    ...typography.caption,
    borderWidth: 1,
    borderColor: colors.border,
    color: colors.textPrimary,
  },
  textarea: { minHeight: 72, textAlignVertical: 'top' },
  chips: { gap: spacing.sm, marginBottom: spacing.sm },
  row2: { flexDirection: 'row', gap: spacing.md },
  half: { flex: 1 },
  switchRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: spacing.xs },
  switchLabel: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.slate700 },
  addBtn: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginVertical: spacing.md },
  addBtnText: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.baytgo },
});
