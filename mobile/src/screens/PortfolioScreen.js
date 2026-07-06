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
  Image,
  Dimensions,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { LinearGradient } from 'expo-linear-gradient';
import * as ImagePicker from 'expo-image-picker';
import { Ionicons } from '@expo/vector-icons';
import TabPageHeader from '../components/TabPageHeader';
import AuthenticatedImage from '../components/AuthenticatedImage';
import ImageLightbox from '../components/ImageLightbox';
import { fetchPortfolio, fetchPortfolioItem, createPortfolio, deletePortfolio } from '../api/portfolio';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

const { width: SCREEN_W } = Dimensions.get('window');
const CARD_W = (SCREEN_W - 40 - 12) / 2;

function StatCard({ label, value, icon }) {
  return (
    <View style={styles.statCard}>
      <View style={styles.statIcon}>
        <Ionicons name={icon} size={16} color={colors.baytgo} />
      </View>
      <Text style={styles.statValue}>{value}</Text>
      <Text style={styles.statLabel}>{label}</Text>
    </View>
  );
}

function AlbumCard({ item, token, onPreview, onEdit, onDelete, deleting }) {
  return (
    <View style={styles.albumCard}>
      <TouchableOpacity style={styles.albumCoverWrap} onPress={onPreview} activeOpacity={0.9}>
        {item.cover_url ? (
          <AuthenticatedImage uri={item.cover_url} token={token} style={styles.albumCover} />
        ) : (
          <View style={[styles.albumCover, styles.albumCoverPlaceholder]}>
            <Ionicons name="images-outline" size={28} color={colors.slate400} />
          </View>
        )}
        <View style={styles.albumOverlay}>
          <Ionicons name="expand-outline" size={16} color={colors.white} />
        </View>
        <View style={styles.photoCount}>
          <Ionicons name="camera-outline" size={11} color={colors.white} />
          <Text style={styles.photoCountText}>{item.images_count || 0}</Text>
        </View>
      </TouchableOpacity>

      <View style={styles.albumMeta}>
        <Text style={styles.albumTitle} numberOfLines={2}>{item.title}</Text>
        <View style={styles.albumActions}>
          <TouchableOpacity style={styles.albumActionBtn} onPress={onEdit} hitSlop={6}>
            <Ionicons name="create-outline" size={16} color={colors.baytgo} />
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.albumActionBtn, styles.albumDeleteBtn]}
            onPress={onDelete}
            disabled={deleting}
            hitSlop={6}
          >
            <Ionicons name="trash-outline" size={16} color="#B91C1C" />
          </TouchableOpacity>
        </View>
      </View>
    </View>
  );
}

export default function PortfolioScreen({ navigation }) {
  const { token } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [deletingId, setDeletingId] = useState(null);
  const [previewingId, setPreviewingId] = useState(null);
  const [albums, setAlbums] = useState([]);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [images, setImages] = useState([]);
  const [lightbox, setLightbox] = useState({ visible: false, images: [], index: 0, title: '' });
  const [formOpen, setFormOpen] = useState(true);

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

  const totalPhotos = useMemo(
    () => albums.reduce((sum, a) => sum + (a.images_count || 0), 0),
    [albums],
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

  const removePick = (index) => {
    setImages((prev) => prev.filter((_, i) => i !== index));
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
      setFormOpen(false);
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

  const handlePreview = async (item) => {
    setPreviewingId(item.id);
    try {
      const data = await fetchPortfolioItem(token, item.id);
      const urls = (data.portfolio?.images || []).map((img) => img.url);
      if (urls.length === 0 && item.cover_url) {
        urls.push(item.cover_url);
      }
      if (urls.length === 0) {
        Alert.alert('Info', 'Album belum memiliki foto.');
        return;
      }
      setLightbox({ visible: true, images: urls, index: 0, title: item.title });
    } catch (err) {
      Alert.alert('Gagal', err.message || 'Tidak dapat memuat foto album');
    } finally {
      setPreviewingId(null);
    }
  };

  return (
    <View style={styles.container}>
      <TabPageHeader title="Portofolio" subtitle="Galeri dokumentasi layanan Anda" />

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
              <Ionicons name="images-outline" size={18} color={colors.baytgo} />
              <Text style={styles.infoText}>
                Unggah foto terbaik saat membimbing jamaah. Album ini tampil di profil publik Anda.
              </Text>
            </View>

            <View style={styles.statsRow}>
              <StatCard label="Album" value={albums.length} icon="albums-outline" />
              <StatCard label="Total foto" value={totalPhotos} icon="camera-outline" />
            </View>

            <TouchableOpacity
              style={styles.formToggle}
              onPress={() => setFormOpen((v) => !v)}
              activeOpacity={0.9}
            >
              <View style={styles.formToggleLeft}>
                <View style={styles.formToggleIcon}>
                  <Ionicons name="add-circle" size={18} color={colors.baytgo} />
                </View>
                <View>
                  <Text style={styles.formToggleTitle}>Tambah album baru</Text>
                  <Text style={styles.formToggleSub}>Judul kegiatan + beberapa foto sekaligus</Text>
                </View>
              </View>
              <Ionicons name={formOpen ? 'chevron-up' : 'chevron-down'} size={20} color={colors.slate500} />
            </TouchableOpacity>

            {formOpen ? (
              <View style={styles.formCard}>
                <Text style={styles.fieldLabel}>Judul kegiatan</Text>
                <TextInput
                  style={styles.input}
                  placeholder="Misal: Ziarah Jabal Rahmah Jamaah VIP"
                  placeholderTextColor={colors.slate400}
                  value={title}
                  onChangeText={setTitle}
                />

                <Text style={styles.fieldLabel}>Deskripsi (opsional)</Text>
                <TextInput
                  style={[styles.input, styles.textarea]}
                  placeholder="Ceritakan singkat pelayanan di foto ini..."
                  placeholderTextColor={colors.slate400}
                  value={description}
                  onChangeText={setDescription}
                  multiline
                  textAlignVertical="top"
                />

                <Text style={styles.fieldLabel}>Foto album</Text>
                <TouchableOpacity style={styles.pickBtn} onPress={pickImages} activeOpacity={0.88}>
                  <Ionicons name="image-outline" size={20} color={colors.baytgo} />
                  <Text style={styles.pickBtnText}>
                    {images.length > 0 ? `Tambah foto (${images.length}/10)` : 'Pilih foto dari galeri'}
                  </Text>
                </TouchableOpacity>

                {images.length > 0 ? (
                  <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.previewRow}>
                    {images.map((img, index) => (
                      <View key={img.uri} style={styles.previewWrap}>
                        <Image source={{ uri: img.uri }} style={styles.preview} />
                        <TouchableOpacity style={styles.previewRemove} onPress={() => removePick(index)}>
                          <Ionicons name="close" size={12} color={colors.white} />
                        </TouchableOpacity>
                      </View>
                    ))}
                  </ScrollView>
                ) : null}

                <TouchableOpacity style={styles.submitBtn} onPress={handleAdd} disabled={submitting} activeOpacity={0.9}>
                  <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.submitGradient}>
                    {submitting ? (
                      <ActivityIndicator color={colors.white} size="small" />
                    ) : (
                      <>
                        <Ionicons name="cloud-upload-outline" size={18} color={colors.white} />
                        <Text style={styles.submitBtnText}>Simpan album</Text>
                      </>
                    )}
                  </LinearGradient>
                </TouchableOpacity>
              </View>
            ) : null}

            <Text style={styles.sectionTitle}>Album saya</Text>

            {albums.length === 0 ? (
              <View style={styles.empty}>
                <View style={styles.emptyIcon}>
                  <Ionicons name="images-outline" size={32} color={colors.slate400} />
                </View>
                <Text style={styles.emptyTitle}>Belum ada album</Text>
                <Text style={styles.emptyText}>
                  Tambahkan album pertama untuk menampilkan dokumentasi layanan ke jamaah.
                </Text>
              </View>
            ) : (
              <View style={styles.grid}>
                {albums.map((item) => (
                  <AlbumCard
                    key={String(item.id)}
                    item={item}
                    token={token}
                    onPreview={() => handlePreview(item)}
                    onEdit={() => navigation.navigate('PortfolioEdit', { portfolioId: item.id })}
                    onDelete={() => handleDelete(item)}
                    deleting={deletingId === item.id || previewingId === item.id}
                  />
                ))}
              </View>
            )}
          </ScrollView>
        </KeyboardAvoidingView>
      )}

      <ImageLightbox
        visible={lightbox.visible}
        images={lightbox.images}
        index={lightbox.index}
        title={lightbox.title}
        token={token}
        onClose={() => setLightbox((s) => ({ ...s, visible: false }))}
        onChangeIndex={(idx) => setLightbox((s) => ({ ...s, index: idx }))}
      />
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
    marginBottom: 14,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.1)',
  },
  infoText: { flex: 1, fontSize: 13, lineHeight: 19, fontWeight: '600', color: colors.baytgo },
  statsRow: { flexDirection: 'row', gap: 10, marginBottom: 14 },
  statCard: {
    flex: 1,
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
  },
  statIcon: {
    width: 32,
    height: 32,
    borderRadius: 10,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
  },
  statValue: { fontSize: 22, fontWeight: '900', color: colors.baytgo },
  statLabel: { marginTop: 2, fontSize: 11, fontWeight: '700', color: colors.slate500 },
  formToggle: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
  },
  formToggleLeft: { flexDirection: 'row', alignItems: 'center', gap: 12, flex: 1 },
  formToggleIcon: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  formToggleTitle: { fontSize: 14, fontWeight: '900', color: colors.baytgo },
  formToggleSub: { marginTop: 2, fontSize: 11, fontWeight: '600', color: colors.slate500 },
  formCard: {
    backgroundColor: colors.white,
    borderRadius: 20,
    padding: 16,
    marginBottom: 20,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.05,
    shadowRadius: 12,
    elevation: 2,
  },
  fieldLabel: { fontSize: 12, fontWeight: '800', color: colors.slate600, marginBottom: 8 },
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
  textarea: { minHeight: 88, textAlignVertical: 'top' },
  pickBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: colors.canvas,
    borderRadius: 14,
    paddingVertical: 16,
    marginBottom: 12,
    borderWidth: 1.5,
    borderColor: colors.baytgo,
    borderStyle: 'dashed',
  },
  pickBtnText: { fontSize: 13, fontWeight: '800', color: colors.baytgo },
  previewRow: { gap: 10, paddingBottom: 14 },
  previewWrap: { position: 'relative' },
  preview: { width: 72, height: 72, borderRadius: 12 },
  previewRemove: {
    position: 'absolute',
    top: 4,
    right: 4,
    width: 20,
    height: 20,
    borderRadius: 10,
    backgroundColor: '#B91C1C',
    alignItems: 'center',
    justifyContent: 'center',
  },
  submitBtn: { borderRadius: 14, overflow: 'hidden' },
  submitGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 15,
  },
  submitBtnText: { color: colors.white, fontWeight: '800', fontSize: 14 },
  sectionTitle: { fontSize: 16, fontWeight: '900', color: colors.baytgo, marginBottom: 14 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  albumCard: {
    width: CARD_W,
    backgroundColor: colors.white,
    borderRadius: 16,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.05,
    shadowRadius: 10,
    elevation: 2,
  },
  albumCoverWrap: { position: 'relative' },
  albumCover: { width: '100%', height: CARD_W * 0.85 },
  albumCoverPlaceholder: {
    backgroundColor: colors.slate100,
    alignItems: 'center',
    justifyContent: 'center',
  },
  albumOverlay: {
    position: 'absolute',
    right: 8,
    bottom: 8,
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: 'rgba(15,46,40,0.55)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  photoCount: {
    position: 'absolute',
    left: 8,
    top: 8,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: 'rgba(15,46,40,0.65)',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 999,
  },
  photoCountText: { fontSize: 11, fontWeight: '800', color: colors.white },
  albumMeta: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 8,
    padding: 10,
  },
  albumTitle: { flex: 1, fontSize: 13, fontWeight: '800', color: colors.slate900, lineHeight: 17 },
  albumActions: { gap: 6 },
  albumActionBtn: {
    width: 30,
    height: 30,
    borderRadius: 8,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  albumDeleteBtn: { backgroundColor: '#FEF2F2' },
  empty: { alignItems: 'center', paddingVertical: 40, paddingHorizontal: 16 },
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
  emptyText: {
    marginTop: 8,
    fontSize: 13,
    fontWeight: '600',
    color: colors.slate500,
    textAlign: 'center',
    lineHeight: 19,
  },
});
