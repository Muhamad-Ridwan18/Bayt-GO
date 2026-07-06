import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  Modal,
  TouchableOpacity,
  Image,
  Dimensions,
  ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import AuthenticatedImage from './AuthenticatedImage';
import { colors } from '../theme/colors';

const { width: SCREEN_W } = Dimensions.get('window');

function LightboxSlide({ uri, token }) {
  if (token) {
    return (
      <AuthenticatedImage
        uri={uri}
        token={token}
        style={styles.slide}
        imageStyle={{ resizeMode: 'contain' }}
      />
    );
  }
  return <Image source={{ uri }} style={styles.slideImage} resizeMode="contain" />;
}

export default function ImageLightbox({ visible, images, index, title, token, onClose, onChangeIndex }) {
  if (!visible || !images?.length) return null;

  const current = images[index];

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.backdrop}>
        <SafeAreaView style={styles.safe}>
          <View style={styles.topBar}>
            <TouchableOpacity style={styles.iconBtn} onPress={onClose} hitSlop={8}>
              <Ionicons name="close" size={22} color={colors.white} />
            </TouchableOpacity>
            <Text style={styles.title} numberOfLines={1}>{title || 'Preview'}</Text>
            <View style={styles.iconBtn} />
          </View>

          <View style={styles.body}>
            {current ? (
              <LightboxSlide uri={current} token={token} />
            ) : (
              <ActivityIndicator color={colors.white} size="large" />
            )}
          </View>

          {images.length > 1 ? (
            <View style={styles.footer}>
              <TouchableOpacity
                style={styles.navBtn}
                onPress={() => onChangeIndex((index - 1 + images.length) % images.length)}
              >
                <Ionicons name="chevron-back" size={22} color={colors.white} />
              </TouchableOpacity>
              <Text style={styles.counter}>{index + 1} / {images.length}</Text>
              <TouchableOpacity
                style={styles.navBtn}
                onPress={() => onChangeIndex((index + 1) % images.length)}
              >
                <Ionicons name="chevron-forward" size={22} color={colors.white} />
              </TouchableOpacity>
            </View>
          ) : null}
        </SafeAreaView>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: { flex: 1, backgroundColor: 'rgba(15,23,42,0.94)' },
  safe: { flex: 1 },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 10,
    gap: 10,
  },
  iconBtn: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  title: { flex: 1, fontSize: 15, fontWeight: '800', color: colors.white, textAlign: 'center' },
  body: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  slide: { width: SCREEN_W, height: SCREEN_W * 0.85 },
  slideImage: { width: SCREEN_W, height: SCREEN_W * 0.85 },
  footer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 24,
    paddingVertical: 16,
  },
  navBtn: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  counter: { fontSize: 14, fontWeight: '700', color: 'rgba(255,255,255,0.8)' },
});
