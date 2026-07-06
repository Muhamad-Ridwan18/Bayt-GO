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
  Dimensions,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { LinearGradient } from 'expo-linear-gradient';
import * as ImagePicker from 'expo-image-picker';
import { Ionicons } from '@expo/vector-icons';
import ScreenHeader from '../components/ScreenHeader';
import AuthenticatedImage from '../components/AuthenticatedImage';
import ImageLightbox from '../components/ImageLightbox';
import { fetchPortfolioItem, updatePortfolio } from '../api/portfolio';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

const { width: SCREEN_W } = Dimensions.get('window');
const IMG_SIZE = (SCREEN_W - 40 - 16) / 3;

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
  const [lightbox, setLightbox] = useState({ visible: false, images: [], index: 0 });

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

  const removeNewImage = (index) => {
    setNewImages((prev) => prev.filter((_, i) => i !== index));
  };

  const toggleDeleteImage = (id) => {
    setDeleteIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  };

  const openPreview = (urls, index) => {
    setLightbox({ visible: true, images: urls, index });
  };

  const handleSave = async () => {
    if (!title.trim()) {
      Alert.alert('Validasi', 'Judul wajib diisi.');
      return;
    }

    const remaining = images.filter((img) => !deleteIds.includes(img.id)).length + newImages.length;
    if (remaining === 0) {
      Alert.alert('Validasi', 'Album harus memiliki minimal satu foto.');
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
  const allPreviewUrls = [
    ...visibleImages.map((img) => img.url),
    ...newImages.map((img) => img.uri),
  ];

  return (
    <View style={styles.container}>
      <ScreenHeader title="Edit album" subtitle={title || 'Kelola foto album'} onBack={() => navigation.goBack()} />

      {loading ? (
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
          >
            <View style={styles.formCard}>
              <Text style={styles.fieldLabel}>Judul kegiatan</Text>
              <TextInput
                style={styles.input}
                placeholder="Judul album"
                placeholderTextColor={colors.slate400}
                value={title}
                onChangeText={setTitle}
              />

              <Text style={styles.fieldLabel}>Deskripsi (opsional)</Text>
              <TextInput
                style={[styles.input, styles.textarea]}
                placeholder="Ceritakan singkat pelayanan..."
                placeholderTextColor={colors.slate400}
                value={description}
                onChangeText={setDescription}
                multiline
                textAlignVertical="top"
              />
            </View>

            <View style={styles.galleryCard}>
              <View style={styles.galleryHead}>
                <Text style={styles.galleryTitle}>Foto dalam album</Text>
                <Text style={styles.galleryCount}>{allPreviewUrls.length} foto</Text>
              </View>

              <View style={styles.grid}>
                {visibleImages.map((img, index) => (
                  <TouchableOpacity
                    key={img.id}
                    style={styles.imageWrap}
                    onPress={() => openPreview(allPreviewUrls, index)}
                    activeOpacity={0.9}
                  >
                    <AuthenticatedImage uri={img.url} token={token} style={styles.image} />
                    <TouchableOpacity
                      style={styles.removeBadge}
                      onPress={() => toggleDeleteImage(img.id)}
                      hitSlop={6}
                    >
                      <Ionicons name="trash-outline" size={12} color={colors.white} />
                    </TouchableOpacity>
                  </TouchableOpacity>
                ))}

                {newImages.map((img, index) => {
                  const previewIndex = visibleImages.length + index;
                  return (
                    <TouchableOpacity
                      key={img.uri}
                      style={styles.imageWrap}
                      onPress={() => openPreview(allPreviewUrls, previewIndex)}
                      activeOpacity={0.9}
                    >
                      <Image source={{ uri: img.uri }} style={styles.imageRaw} />
                      <View style={styles.newBadge}>
                        <Text style={styles.newBadgeText}>Baru</Text>
                      </View>
                      <TouchableOpacity
                        style={styles.removeBadge}
                        onPress={() => removeNewImage(index)}
                        hitSlop={6}
                      >
                        <Ionicons name="close" size={12} color={colors.white} />
                      </TouchableOpacity>
                    </TouchableOpacity>
                  );
                })}

                <TouchableOpacity style={styles.addTile} onPress={pickNewImages} activeOpacity={0.88}>
                  <Ionicons name="add" size={28} color={colors.baytgo} />
                  <Text style={styles.addTileText}>Tambah</Text>
                </TouchableOpacity>
              </View>

              <Text style={styles.hint}>Ketuk foto untuk preview · ikon sampah untuk hapus</Text>
            </View>

            <TouchableOpacity style={styles.saveBtn} onPress={handleSave} disabled={saving} activeOpacity={0.9}>
              <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.saveGradient}>
                {saving ? (
                  <ActivityIndicator color={colors.white} size="small" />
                ) : (
                  <>
                    <Ionicons name="checkmark-circle-outline" size={18} color={colors.white} />
                    <Text style={styles.saveBtnText}>Simpan perubahan</Text>
                  </>
                )}
              </LinearGradient>
            </TouchableOpacity>
          </ScrollView>
        </KeyboardAvoidingView>
      )}

      <ImageLightbox
        visible={lightbox.visible}
        images={lightbox.images}
        index={lightbox.index}
        title={title}
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
  loader: { marginTop: 40 },
  scroll: { padding: 20, paddingBottom: 40 },
  formCard: {
    backgroundColor: colors.white,
    borderRadius: 20,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
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
  galleryCard: {
    backgroundColor: colors.white,
    borderRadius: 20,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
  },
  galleryHead: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 14,
  },
  galleryTitle: { fontSize: 14, fontWeight: '900', color: colors.baytgo },
  galleryCount: { fontSize: 12, fontWeight: '700', color: colors.slate500 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  imageWrap: {
    width: IMG_SIZE,
    height: IMG_SIZE,
    borderRadius: 12,
    overflow: 'hidden',
    backgroundColor: colors.slate100,
  },
  image: { width: IMG_SIZE, height: IMG_SIZE },
  imageRaw: { width: IMG_SIZE, height: IMG_SIZE },
  removeBadge: {
    position: 'absolute',
    top: 6,
    right: 6,
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: 'rgba(185,28,28,0.9)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  newBadge: {
    position: 'absolute',
    left: 6,
    bottom: 6,
    backgroundColor: colors.baytgo,
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
  },
  newBadgeText: { fontSize: 9, fontWeight: '800', color: colors.white },
  addTile: {
    width: IMG_SIZE,
    height: IMG_SIZE,
    borderRadius: 12,
    borderWidth: 1.5,
    borderColor: colors.baytgo,
    borderStyle: 'dashed',
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
  },
  addTileText: { fontSize: 11, fontWeight: '800', color: colors.baytgo },
  hint: { marginTop: 12, fontSize: 11, fontWeight: '600', color: colors.slate400, textAlign: 'center' },
  saveBtn: { borderRadius: 14, overflow: 'hidden' },
  saveGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 15,
  },
  saveBtnText: { color: colors.white, fontWeight: '800', fontSize: 14 },
});
