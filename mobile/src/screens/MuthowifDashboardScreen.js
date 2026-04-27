import React, { useState, useEffect } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  TouchableOpacity, 
  ScrollView, 
  Dimensions,
  ActivityIndicator,
  RefreshControl
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { Calendar, LocaleConfig } from 'react-native-calendars';
import { apiClient } from '../api/client';
import { Skeleton, SkeletonCard, SkeletonText } from '../components/Skeleton';

LocaleConfig.locales['id'] = {
  monthNames: ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
  monthNamesShort: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
  dayNames: ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'],
  dayNamesShort: ['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
  today: 'Hari ini'
};
LocaleConfig.defaultLocale = 'id';

const { width } = Dimensions.get('window');

export default function MuthowifDashboardScreen({ user, onLogout, navigation }) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [dashboardData, setDashboardData] = useState({
    stats: [],
    recent_schedules: [],
    unread_messages: 0,
  });
  const [markedDates, setMarkedDates] = useState({});

  const fetchData = async () => {
    try {
      const data = await apiClient.getMuthowifDashboardData(user.token);
      setDashboardData(data);
      
      const marked = {};
      if (data.recent_schedules && Array.isArray(data.recent_schedules)) {
        data.recent_schedules.forEach(item => {
          if (item.raw_date) {
            marked[item.raw_date] = { 
              customStyles: {
                container: { backgroundColor: '#E0F2FE', borderRadius: 8, height: 40, justifyContent: 'center' },
                text: { color: '#0369A1', fontWeight: '800' }
              }
            };
          }
        });
      }
      setMarkedDates(marked);
    } catch (error) {
      console.error('Fetch Muthowif Dashboard Error:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchData();
    const interval = setInterval(() => {
      apiClient.getMuthowifDashboardData(user.token).then(data => {
        setDashboardData(prev => ({ ...prev, unread_messages: data.unread_messages }));
      }).catch(() => {});
    }, 5000);
    return () => clearInterval(interval);
  }, []);

  if (loading) {
    return (
      <SafeAreaView style={styles.container}>
        <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
          {/* Profile card skeleton */}
          <SkeletonCard style={{ flexDirection: 'row', alignItems: 'center', gap: 14, marginBottom: 25 }}>
            <Skeleton width={56} height={56} borderRadius={16} />
            <View style={{ flex: 1 }}>
              <SkeletonText width="40%" height={11} />
              <SkeletonText width="65%" height={18} style={{ marginBottom: 0 }} />
            </View>
          </SkeletonCard>
          {/* Wallet card skeleton */}
          <Skeleton width="100%" height={100} borderRadius={24} style={{ marginBottom: 25 }} />
          {/* Shortcut grid */}
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', marginBottom: 30 }}>
            {[1,2,3,4].map(i => (
              <View key={i} style={{ alignItems: 'center', width: '22%' }}>
                <Skeleton width={55} height={55} borderRadius={18} style={{ marginBottom: 8 }} />
                <Skeleton width="80%" height={11} borderRadius={6} />
              </View>
            ))}
          </View>
          {/* Stats row */}
          <View style={{ flexDirection: 'row', gap: 15, marginBottom: 30 }}>
            {[1,2].map(i => (
              <SkeletonCard key={i} style={{ flex: 1, marginBottom: 0 }}>
                <SkeletonText width="50%" height={20} />
                <SkeletonText width="70%" height={11} style={{ marginBottom: 0 }} />
              </SkeletonCard>
            ))}
          </View>
          {/* Calendar placeholder */}
          <Skeleton width="100%" height={280} borderRadius={24} style={{ marginBottom: 30 }} />
          {/* Task list */}
          {[1,2,3].map(i => (
            <SkeletonCard key={i}>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 14 }}>
                <Skeleton width={40} height={40} borderRadius={12} />
                <View style={{ flex: 1 }}>
                  <SkeletonText width="60%" height={14} />
                  <SkeletonText width="40%" height={11} style={{ marginBottom: 0 }} />
                </View>
                <Skeleton width={60} height={26} borderRadius={8} />
              </View>
            </SkeletonCard>
          ))}
        </ScrollView>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />
      
      <ScrollView 
        showsVerticalScrollIndicator={false} 
        contentContainerStyle={styles.scrollContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={fetchData} color="#0984e3" />}
      >
        {/* 1. Profile Header in a Beautiful Card */}
        <View style={styles.profileCard}>
          <View style={styles.profileMain}>
            <View style={styles.avatarGlow}>
              <View style={styles.avatar}>
                <Text style={styles.avatarText}>{user?.user?.name?.charAt(0) || 'M'}</Text>
              </View>
            </View>
            <View style={styles.nameCol}>
              <Text style={styles.helloText}>Ahlan wa Sahlan,</Text>
              <Text style={styles.nameText}>{user?.user?.name || 'Muthowif'}</Text>
              <View style={styles.badge}>
                <Text style={styles.badgeText}>Verified Partner</Text>
              </View>
            </View>
          </View>
          <TouchableOpacity style={styles.notifBtn}>
            <Text style={{fontSize: 22}}>🔔</Text>
          </TouchableOpacity>
        </View>

        {/* 2. Wallet Card (Brand Blue Style) */}
        <TouchableOpacity 
          style={styles.walletCard} 
          onPress={() => navigation.navigate('Wallet')}
          activeOpacity={0.9}
        >
          <View style={styles.walletHeader}>
            <View>
              <Text style={styles.walletLabel}>SALDO DOMPET</Text>
              <Text style={styles.walletValue}>{dashboardData.stats[2]?.value || 'Rp 0'}</Text>
            </View>
            <View style={styles.withdrawAction}>
              <Text style={styles.withdrawBtnText}>Tarik Dana</Text>
            </View>
          </View>
          <View style={styles.walletInfo}>
            <Text style={styles.walletSub}>Pendapatan aman & terverifikasi</Text>
          </View>
        </TouchableOpacity>

        {/* Shortcut Menu (Quick Access) */}
        <View style={styles.shortcutGrid}>
          <TouchableOpacity style={styles.shortcutItem} onPress={() => navigation.navigate('MuthowifBookings')}>
            <View style={styles.shortcutIcon}><Text style={{fontSize:22}}>📋</Text></View>
            <Text style={styles.shortcutLabel}>Bookings</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.shortcutItem} onPress={() => navigation.navigate('Services')}>
            <View style={styles.shortcutIcon}><Text style={{fontSize:22}}>🛠️</Text></View>
            <Text style={styles.shortcutLabel}>Layanan</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.shortcutItem} onPress={() => navigation.navigate('Wallet')}>
            <View style={styles.shortcutIcon}><Text style={{fontSize:22}}>💰</Text></View>
            <Text style={styles.shortcutLabel}>Dompet</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.shortcutItem} onPress={() => navigation.navigate('Schedule')}>
            <View style={styles.shortcutIcon}><Text style={{fontSize:22}}>📅</Text></View>
            <Text style={styles.shortcutLabel}>Jadwal</Text>
          </TouchableOpacity>
        </View>

        {/* 3. Quick Summary Grid */}
        <View style={styles.statsRow}>
          <View style={styles.statBox}>
            <View style={[styles.statIcon, {backgroundColor: '#ECFDF5'}]}><Text>📅</Text></View>
            <View>
              <Text style={styles.statVal}>{dashboardData.stats[0]?.value || '0'}</Text>
              <Text style={styles.statLbl}>Jadwal</Text>
            </View>
          </View>
          <View style={styles.statBox}>
            <View style={[styles.statIcon, {backgroundColor: '#EFF6FF'}]}><Text>✅</Text></View>
            <View>
              <Text style={styles.statVal}>{dashboardData.stats[1]?.value || '0'}</Text>
              <Text style={styles.statLbl}>Selesai</Text>
            </View>
          </View>
        </View>

        {/* 4. Calendar Section */}
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Agenda Tugas</Text>
          <TouchableOpacity><Text style={styles.seeAll}>Lengkap →</Text></TouchableOpacity>
        </View>
        <View style={styles.calendarContainer}>
          <Calendar
            markingType={'custom'}
            markedDates={markedDates}
            monthFormat={'MMMM yyyy'}
            theme={{
              calendarBackground: 'transparent',
              textSectionTitleColor: '#94A3B8',
              todayTextColor: '#0984e3',
              dayTextColor: '#1E293B',
              textMonthFontWeight: '800',
              textDayHeaderFontWeight: '800',
            }}
          />
        </View>

        {/* 5. Task List */}
        <Text style={styles.sectionTitle}>Jadwal Mendatang</Text>
        {dashboardData.recent_schedules.map((item, index) => (
          <TouchableOpacity key={index} style={styles.taskCard}>
            <View style={styles.taskLeft}>
              <View style={styles.taskIcon}><Text>🕋</Text></View>
              <View>
                <Text style={styles.taskName}>{item.customer_name}</Text>
                <Text style={styles.taskMeta}>{item.date} • {item.service_name || 'Bimbingan'}</Text>
              </View>
            </View>
            <View style={styles.statusBadge}>
              <Text style={styles.statusText}>{item.status}</Text>
            </View>
          </TouchableOpacity>
        ))}

      </ScrollView>

      {/* 6. Professional Bottom Nav */}
      <View style={styles.bottomNav}>
        <TouchableOpacity style={styles.navItem} onPress={() => navigation.navigate('Dashboard')}>
          <View style={styles.navActive}><Text style={{fontSize:22}}>🏠</Text></View>
          <Text style={styles.navLabelActive}>Home</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.navItem} onPress={() => navigation.navigate('ChatList')}>
          <View>
            <Text style={{fontSize:22}}>💬</Text>
            {dashboardData.unread_messages > 0 && (
              <View style={styles.badgeTab}>
                <Text style={styles.badgeTabText}>{dashboardData.unread_messages > 99 ? '99+' : dashboardData.unread_messages}</Text>
              </View>
            )}
          </View>
          <Text style={styles.navLabel}>Pesan</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.navItem} onPress={() => navigation.navigate('Wallet')}><Text style={{fontSize:22}}>👛</Text><Text style={styles.navLabel}>Dompet</Text></TouchableOpacity>
        <TouchableOpacity style={styles.navItem} onPress={() => navigation.navigate('Profile')}><Text style={{fontSize:22}}>👤</Text><Text style={styles.navLabel}>Profil</Text></TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  scrollContent: { paddingHorizontal: 20, paddingBottom: 130, paddingTop: 20 },
  
  profileCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 24,
    padding: 20,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 25,
    shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.05, shadowRadius: 10, elevation: 3
  },
  profileMain: { flexDirection: 'row', alignItems: 'center' },
  avatarGlow: { padding: 3, borderRadius: 18, backgroundColor: '#E0F2FE', marginRight: 15 },
  avatar: { width: 50, height: 50, borderRadius: 15, backgroundColor: '#0984e3', justifyContent: 'center', alignItems: 'center' },
  avatarText: { color: '#ffffff', fontSize: 20, fontWeight: '800' },
  helloText: { color: '#94A3B8', fontSize: 11, fontWeight: '600' },
  nameText: { color: '#1E293B', fontSize: 18, fontWeight: '800' },
  badge: { backgroundColor: '#F1F5F9', paddingHorizontal: 8, paddingVertical: 2, borderRadius: 6, marginTop: 4, alignSelf: 'flex-start' },
  badgeText: { color: '#64748B', fontSize: 9, fontWeight: '700' },
  notifBtn: { padding: 5 },
  
  shortcutGrid: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 30,
    paddingHorizontal: 5,
  },
  shortcutItem: {
    alignItems: 'center',
    width: (width - 60) / 4,
  },
  shortcutIcon: {
    width: 55,
    height: 55,
    backgroundColor: '#FFFFFF',
    borderRadius: 18,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 8,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 5,
    elevation: 2,
  },
  shortcutLabel: {
    fontSize: 11,
    fontWeight: '700',
    color: '#64748B',
    textAlign: 'center',
  },

  walletCard: { 
    backgroundColor: '#0984e3', 
    borderRadius: 24, 
    padding: 22, 
    marginBottom: 25,
    shadowColor: '#0984e3', shadowOffset: { width: 0, height: 10 }, shadowOpacity: 0.15, shadowRadius: 20, elevation: 8
  },
  walletHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  walletLabel: { color: 'rgba(255,255,255,0.6)', fontSize: 10, fontWeight: '800', letterSpacing: 1 },
  walletValue: { color: '#ffffff', fontSize: 24, fontWeight: '900', marginTop: 5 },
  withdrawAction: { backgroundColor: 'rgba(255,255,255,0.2)', paddingHorizontal: 15, paddingVertical: 10, borderRadius: 12 },
  withdrawBtnText: { color: '#ffffff', fontSize: 12, fontWeight: '700' },
  walletInfo: { marginTop: 15, paddingTop: 15, borderTopWidth: 1, borderTopColor: 'rgba(255,255,255,0.1)' },
  walletSub: { color: 'rgba(255,255,255,0.7)', fontSize: 11, fontWeight: '600' },

  statsRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 30 },
  statBox: { 
    width: (width - 55) / 2, 
    backgroundColor: '#ffffff', 
    borderRadius: 20, 
    padding: 15, 
    flexDirection: 'row', 
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#F1F5F9'
  },
  statIcon: { width: 36, height: 36, borderRadius: 10, justifyContent: 'center', alignItems: 'center', marginRight: 12 },
  statVal: { fontSize: 18, fontWeight: '800', color: '#1E293B' },
  statLbl: { fontSize: 11, color: '#94A3B8', fontWeight: '700' },

  sectionHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 15 },
  sectionTitle: { fontSize: 18, fontWeight: '800', color: '#1E293B', marginBottom: 15 },
  seeAll: { fontSize: 13, color: '#0984e3', fontWeight: '700' },
  
  calendarContainer: { backgroundColor: '#ffffff', borderRadius: 24, padding: 10, marginBottom: 35, borderWidth: 1, borderColor: '#F1F5F9' },

  taskCard: { 
    backgroundColor: '#ffffff', 
    borderRadius: 20, 
    padding: 18, 
    flexDirection: 'row', 
    justifyContent: 'space-between', 
    alignItems: 'center', 
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#F1F5F9'
  },
  taskLeft: { flexDirection: 'row', alignItems: 'center' },
  taskIcon: { width: 40, height: 40, borderRadius: 12, backgroundColor: '#F8FAFC', justifyContent: 'center', alignItems: 'center', marginRight: 15 },
  taskName: { fontSize: 15, fontWeight: '800', color: '#1E293B' },
  taskMeta: { fontSize: 11, color: '#94A3B8', marginTop: 4 },
  statusBadge: { backgroundColor: '#F0F9FF', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 10 },
  statusText: { color: '#0369A1', fontSize: 10, fontWeight: '800' },

  bottomNav: {
    position: 'absolute', bottom: 0, left: 0, right: 0, height: 85,
    backgroundColor: '#FFFFFF', flexDirection: 'row', justifyContent: 'space-around', alignItems: 'center',
    paddingBottom: 25, borderTopWidth: 1, borderTopColor: '#F1F5F9',
  },
  navItem: { alignItems: 'center', flex: 1 },
  navActive: { backgroundColor: '#F0F9FF', width: 45, height: 45, borderRadius: 15, justifyContent: 'center', alignItems: 'center', marginBottom: 4 },
  navLabel: { fontSize: 10, fontWeight: '700', color: '#94A3B8' },
  navLabelActive: { fontSize: 10, fontWeight: '800', color: '#0984e3' },
  badgeTab: {
    position: 'absolute',
    top: -4,
    right: -8,
    backgroundColor: '#EF4444',
    borderRadius: 8,
    minWidth: 16,
    height: 16,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 4,
  },
  badgeTabText: {
    color: '#FFFFFF',
    fontSize: 8,
    fontWeight: '800',
  }
});
