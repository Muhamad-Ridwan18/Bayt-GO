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
  Image,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import * as ImagePicker from 'expo-image-picker';
import { Ionicons } from '@expo/vector-icons';
import TabPageHeader from '../components/TabPageHeader';
import AuthenticatedImage from '../components/AuthenticatedImage';
import { fetchPortfolio, createPortfolio, deletePortfolio } from '../api/portfolio';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

function AlbumRow({ item, token, onPress, onDelete, deleting }) {
  return (
    <TouchableOpacity style={styles.row} onPress={onPress} activeOpacity={0.9}>
      {item.cover_url ? (
        <AuthenticatedImage uri={item.cover_url} token={token} style={styles.cover} />
      ) : (
        <View style={[styles.cover, styles.coverPlaceholder]}>
          <Ionicons name="images-outline" size={22} color={colors.slate400} />
        </View>
      )}
      <View style={styles.rowMeta}>
        <Text style={styles.rowTitle}>{item.title}</Text>
        <Text style={styles.rowCount}>{item.images_count || 0} foto</Text>
      </View>
      <TouchableOpacity
        style={styles.deleteBtn}
        onPress={(e) => {
          e?.stopPropagation?.();
          onDelete(item);
        }}
        disabled={deleting}
        hitSlop={8}
      >
        <Ionicons name="trash-outline" size={18} color="#B91C1C" />
      </TouchableOpacity>
    </TouchableOpacity>
  );
}

export default function PortfolioScreen({ navigation }) {
  const { token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [deletingId, setDeletingId] = useState(null);
  const [albums, setAlbums] = useState([]);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [images, setImages] = useState([]);

  const load = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchPortfolio(token);
      setAlbums(data.portfolios || []);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat memuat portofolio');
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

  const pickImages = async () => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      Alert.alert('Izin diperlukan', 'Izinkan akses galeri untuk menambah foto.');
      return;
    }

    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsMultipleSelection: true,
      selectionLimit: 10,
      quality: 0.85,
    });

    if (!result.canceled && result.assets?.length) {
      setImages((prev) => [...prev, ...result.assets].slice(0, 10));
    }
  };

  const handleAdd = async () => {
    if (!title.trim()) {
      Alert.alert('Validasi', 'Judul album wajib diisi.');
      return;
    }
    if (images.length === 0) {
      Alert.alert('Validasi', 'Pilih minimal satu foto.');
      return;
    }

    const formData = new FormData();
    formData.append('title', title.trim());
    if (description.trim()) formData.append('description', description.trim());
    images.forEach((img, index) => {
      formData.append(`images[${index}]`, {
        uri: img.uri,
        name: img.fileName || `portfolio-${index}.jpg`,
        type: img.mimeType || 'image/jpeg',
      });
    });

    setSubmitting(true);
    try {
      await createPortfolio(token, formData);
      Alert.alert('Berhasil', 'Album portofolio ditambahkan.');
      setTitle('');
      setDescription('');
      setImages([]);
      await load(true);
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat menambah portofolio');
    } finally {
      setSubmitting(false);
    }
  };

  const handleDelete = (item) => {
    Alert.alert('Hapus album?', `Hapus "${item.title}"?`, [
      { text: 'Batal', style: 'cancel' },
      {
        text: 'Hapus',
        style: 'destructive',
        onPress: async () => {
          setDeletingId(item.id);
          try {
            await deletePortfolio(token, item.id);
            await load(true);
          } catch (err) {
            Alert.alert('Gagal', err.message || 'Tidak dapat menghapus album');
          } finally {
            setDeletingId(null);
          }
        },
      },
    ]);
  };

  return (
    <View style={styles.container}>
      <TabPageHeader title="Portofolio" subtitle="Album dokumentasi layanan" />

      {loading && !refreshing ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <ScrollView
          contentContainerStyle={styles.scroll}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />
          }
        >
          <View style={styles.formCard}>
            <Text style={styles.formTitle}>Tambah album</Text>
            <TextInput
              style={styles.input}
              placeholder="Judul album"
              value={title}
              onChangeText={setTitle}
            />
            <TextInput
              style={[styles.input, styles.textarea]}
              placeholder="Deskripsi (opsional)"
              value={description}
              onChangeText={setDescription}
              multiline
            />
            <TouchableOpacity style={styles.pickBtn} onPress={pickImages}>
              <Ionicons name="image-outline" size={18} color={colors.baytgo} />
              <Text style={styles.pickBtnText}>
                {images.length > 0 ? `${images.length} foto dipilih` : 'Pilih foto dari galeri'}
              </Text>
            </TouchableOpacity>
            {images.length > 0 ? (
              <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.previewRow}>
                {images.map((img) => (
                  <Image key={img.uri} source={{ uri: img.uri }} style={styles.preview} />
                ))}
              </ScrollView>
            ) : null}
            <TouchableOpacity
              style={[styles.submitBtn, submitting && styles.submitBtnDisabled]}
              onPress={handleAdd}
              disabled={submitting}
            >
              <Text style={styles.submitBtnText}>{submitting ? 'Mengunggah…' : 'Simpan album'}</Text>
            </TouchableOpacity>
          </View>

          <Text style={styles.sectionTitle}>Album saya</Text>
          {albums.length === 0 ? (
            <Text style={styles.muted}>Belum ada album portofolio.</Text>
          ) : (
            albums.map((item) => (
              <AlbumRow
                key={String(item.id)}
                item={item}
                token={token}
                onPress={() => navigation.navigate('PortfolioEdit', { portfolioId: item.id })}
                onDelete={handleDelete}
                deleting={deletingId === item.id}
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
  formCard: {
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  formTitle: { fontSize: 16, fontWeight: '900', color: colors.baytgo, marginBottom: 12 },
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
  textarea: { minHeight: 72, textAlignVertical: 'top' },
  pickBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: colors.canvas,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: colors.slate100,
    borderStyle: 'dashed',
  },
  pickBtnText: { fontSize: 13, fontWeight: '700', color: colors.baytgo },
  previewRow: { marginBottom: 10 },
  preview: { width: 64, height: 64, borderRadius: 10, marginRight: 8 },
  submitBtn: {
    marginTop: 4,
    backgroundColor: colors.baytgo,
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
  },
  submitBtnDisabled: { opacity: 0.6 },
  submitBtnText: { color: colors.white, fontWeight: '800', fontSize: 14 },
  sectionTitle: { fontSize: 18, fontWeight: '900', color: colors.baytgo, marginBottom: 12 },
  muted: { fontSize: 14, color: colors.slate500, fontWeight: '600' },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: colors.white,
    borderRadius: 14,
    padding: 12,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  cover: { width: 52, height: 52, borderRadius: 10 },
  coverPlaceholder: {
    backgroundColor: colors.canvas,
    alignItems: 'center',
    justifyContent: 'center',
  },
  rowMeta: { flex: 1 },
  rowTitle: { fontSize: 14, fontWeight: '800', color: colors.slate900 },
  rowCount: { marginTop: 4, fontSize: 11, fontWeight: '600', color: colors.slate500 },
  deleteBtn: {
    width: 36,
    height: 36,
    borderRadius: 10,
    backgroundColor: '#FEF2F2',
    alignItems: 'center',
    justifyContent: 'center',
  },
});
