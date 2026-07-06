import React, { useCallback, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  ActivityIndicator,
  TouchableOpacity,
  TextInput,
  Alert,
  Image,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import * as ImagePicker from 'expo-image-picker';
import { Ionicons } from '@expo/vector-icons';
import ScreenHeader from '../components/ScreenHeader';
import AuthenticatedImage from '../components/AuthenticatedImage';
import { fetchPortfolioItem, updatePortfolio } from '../api/portfolio';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

export default function PortfolioEditScreen({ navigation, route }) {
  const { portfolioId } = route.params;
  const { token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [images, setImages] = useState([]);
  const [deleteIds, setDeleteIds] = useState([]);
  const [newImages, setNewImages] = useState([]);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await fetchPortfolioItem(token, portfolioId);
      const p = data.portfolio;
      setTitle(p.title || '');
      setDescription(p.description || '');
      setImages(p.images || []);
      setDeleteIds([]);
      setNewImages([]);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat memuat album');
      navigation.goBack();
    } finally {
      setLoading(false);
    }
  }, [token, portfolioId, navigation]);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

  const pickNewImages = async () => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      Alert.alert('Izin diperlukan', 'Izinkan akses galeri.');
      return;
    }
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsMultipleSelection: true,
      selectionLimit: 10,
      quality: 0.85,
    });
    if (!result.canceled && result.assets?.length) {
      setNewImages((prev) => [...prev, ...result.assets].slice(0, 10));
    }
  };

  const toggleDeleteImage = (id) => {
    setDeleteIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  };

  const handleSave = async () => {
    if (!title.trim()) {
      Alert.alert('Validasi', 'Judul wajib diisi.');
      return;
    }

    setSaving(true);
    try {
      await updatePortfolio(token, portfolioId, {
        title: title.trim(),
        description: description.trim() || null,
        newImages,
        deleteImageIds: deleteIds,
      });
      Alert.alert('Berhasil', 'Album diperbarui.', [{ text: 'OK', onPress: () => navigation.goBack() }]);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat menyimpan');
    } finally {
      setSaving(false);
    }
  };

  const visibleImages = images.filter((img) => !deleteIds.includes(img.id));

  return (
    <View style={styles.container}>
      <ScreenHeader title="Edit album" onBack={() => navigation.goBack()} />

      {loading ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <ScrollView contentContainerStyle={styles.scroll}>
          <TextInput style={styles.input} placeholder="Judul" value={title} onChangeText={setTitle} />
          <TextInput
            style={[styles.input, styles.textarea]}
            placeholder="Deskripsi (opsional)"
            value={description}
            onChangeText={setDescription}
            multiline
          />

          <Text style={styles.sectionTitle}>Foto dalam album</Text>
          <View style={styles.grid}>
            {visibleImages.map((img) => (
              <View key={img.id} style={styles.imageWrap}>
                <AuthenticatedImage uri={img.url} token={token} style={styles.image} />
                <TouchableOpacity style={styles.removeBadge} onPress={() => toggleDeleteImage(img.id)}>
                  <Ionicons name="close" size={14} color={colors.white} />
                </TouchableOpacity>
              </View>
            ))}
            {newImages.map((img) => (
              <View key={img.uri} style={styles.imageWrap}>
                <Image source={{ uri: img.uri }} style={styles.image} />
              </View>
            ))}
          </View>

          <TouchableOpacity style={styles.pickBtn} onPress={pickNewImages}>
            <Ionicons name="add-circle-outline" size={18} color={colors.baytgo} />
            <Text style={styles.pickBtnText}>Tambah foto baru</Text>
          </TouchableOpacity>

          <TouchableOpacity style={[styles.saveBtn, saving && styles.saveBtnDisabled]} onPress={handleSave} disabled={saving}>
            <Text style={styles.saveBtnText}>{saving ? 'Menyimpan…' : 'Simpan perubahan'}</Text>
          </TouchableOpacity>
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  loader: { marginTop: 40 },
  scroll: { padding: 20, paddingBottom: 32 },
  input: {
    backgroundColor: colors.white,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    marginBottom: 10,
    fontSize: 14,
    fontWeight: '600',
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  textarea: { minHeight: 80, textAlignVertical: 'top' },
  sectionTitle: { fontSize: 14, fontWeight: '900', color: colors.baytgo, marginVertical: 12 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  imageWrap: { width: 96, height: 96, borderRadius: 10, overflow: 'hidden' },
  image: { width: 96, height: 96 },
  removeBadge: {
    position: 'absolute',
    top: 4,
    right: 4,
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: '#B91C1C',
    alignItems: 'center',
    justifyContent: 'center',
  },
  pickBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginTop: 12,
    marginBottom: 16,
  },
  pickBtnText: { fontSize: 13, fontWeight: '800', color: colors.baytgo },
  saveBtn: {
    backgroundColor: colors.baytgo,
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
  },
  saveBtnDisabled: { opacity: 0.6 },
  saveBtnText: { color: colors.white, fontWeight: '800', fontSize: 14 },
});
