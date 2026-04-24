import React from 'react';
import { StyleSheet, Text, View, TouchableOpacity } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';

export default function OpeningScreen({ navigation }) {
  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />
      
      <View style={styles.content}>
        <View style={styles.brandWrapper}>
          <View style={styles.dot} />
          <Text style={styles.brandName}>Bayt-GO</Text>
        </View>

        <View style={styles.mainContent}>
          <Text style={styles.headline}>Bimbingan Ibadah dalam Genggaman.</Text>
          <Text style={styles.subHeadline}>
            Pengalaman spiritual yang lebih personal, aman, dan terpercaya untuk setiap langkah perjalanan suci Anda.
          </Text>
        </View>
      </View>

      <View style={styles.footer}>
        <TouchableOpacity 
          activeOpacity={0.8}
          style={styles.primaryButton}
          onPress={() => navigation.navigate('Login')}
        >
          <Text style={styles.primaryButtonText}>Mulai Sekarang</Text>
        </TouchableOpacity>

        <TouchableOpacity 
          activeOpacity={0.6}
          style={styles.secondaryButton}
          onPress={() => navigation.navigate('Register')}
        >
          <Text style={styles.secondaryButtonText}>Daftar Akun Baru</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC', // Abu-abu sangat muda (Airy)
  },
  content: {
    flex: 1,
    paddingHorizontal: 40,
    justifyContent: 'center',
  },
  brandWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 30,
  },
  dot: {
    width: 12,
    height: 12,
    borderRadius: 6,
    backgroundColor: '#0984e3',
    marginRight: 12,
  },
  brandName: {
    fontSize: 20,
    fontWeight: '800',
    color: '#0F172A',
    letterSpacing: 1.5,
    textTransform: 'uppercase',
  },
  mainContent: {
    marginTop: 20,
  },
  headline: {
    fontSize: 38,
    fontWeight: '700',
    color: '#0F172A',
    lineHeight: 48,
    letterSpacing: -1,
  },
  subHeadline: {
    fontSize: 16,
    color: '#64748B',
    lineHeight: 28,
    marginTop: 25,
    fontWeight: '400',
  },
  footer: {
    paddingHorizontal: 40,
    paddingBottom: 50,
  },
  primaryButton: {
    backgroundColor: '#0984e3',
    paddingVertical: 20,
    borderRadius: 18,
    alignItems: 'center',
    shadowColor: '#0984e3',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.2,
    shadowRadius: 20,
    elevation: 8,
  },
  primaryButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
    letterSpacing: 0.5,
  },
  secondaryButton: {
    marginTop: 20,
    paddingVertical: 10,
    alignItems: 'center',
  },
  secondaryButtonText: {
    color: '#64748B',
    fontSize: 15,
    fontWeight: '500',
  },
});
