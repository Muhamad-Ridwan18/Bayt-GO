import React from 'react';
import { View, Text, StyleSheet, ScrollView } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import TabPageHeader from '../components/TabPageHeader';
import { colors } from '../theme/colors';

export default function MuthowifPendingScreen() {
  const { user } = useAuth();

  return (
    <View style={styles.safe}>
      <LinearGradient colors={[colors.canvas, colors.white]} style={StyleSheet.absoluteFill} />
      <TabPageHeader title="Beranda" subtitle={`Halo, ${user?.name || 'Muthowif'}`} />
      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.card}>
          <View style={styles.iconWrap}>
            <Ionicons name="time-outline" size={32} color="#B45309" />
          </View>
          <Text style={styles.title}>Profil sedang ditinjau</Text>
          <Text style={styles.body}>
            Pendaftaran muthowif Anda sudah kami terima. Tim admin akan memverifikasi dokumen dan profil Anda.
            Setelah disetujui, Anda bisa menerima permintaan booking lewat aplikasi.
          </Text>
          <View style={styles.hintBox}>
            <Text style={styles.hintTitle}>Sementara ini</Text>
            <Text style={styles.hintText}>• Pantau status lewat tab Profil{'\n'}• Anda akan mendapat notifikasi setelah disetujui</Text>
          </View>
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.canvas },
  scroll: { padding: 20, paddingBottom: 32 },
  card: {
    backgroundColor: colors.white,
    borderRadius: 24,
    padding: 24,
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  iconWrap: {
    width: 64,
    height: 64,
    borderRadius: 20,
    backgroundColor: '#FFFBEB',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  title: { fontSize: 22, fontWeight: '900', color: colors.slate900 },
  body: { marginTop: 10, fontSize: 14, lineHeight: 22, color: colors.slate600, fontWeight: '600' },
  hintBox: {
    marginTop: 20,
    backgroundColor: colors.canvas,
    borderRadius: 16,
    padding: 16,
  },
  hintTitle: { fontSize: 12, fontWeight: '800', color: colors.baytgo, textTransform: 'uppercase' },
  hintText: { marginTop: 8, fontSize: 13, lineHeight: 20, color: colors.slate600, fontWeight: '600' },
});
