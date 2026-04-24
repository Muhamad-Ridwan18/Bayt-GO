import React, { useRef } from 'react';
import { Animated, PanResponder, Dimensions, StyleSheet, View } from 'react-native';

const { width } = Dimensions.get('window');

/**
 * SwipeableScreen — Swipe dari kiri (< 60px) ke kanan untuk kembali.
 * Fitur:
 *  - Shadow tepi kiri (kesan kedalaman layer)
 *  - Overlay gelap yang memudar saat drag
 *  - Spring physics yang natural
 *  - useNativeDriver untuk 60fps
 */
export default function SwipeableScreen({ children, onSwipeBack, style }) {
  const translateX = useRef(new Animated.Value(0)).current;

  // Overlay opacity: 0.3 saat awal, 0 saat sudah di tepi kanan
  const overlayOpacity = translateX.interpolate({
    inputRange: [0, width],
    outputRange: [0.15, 0],
    extrapolate: 'clamp',
  });

  // Shadow edge opacity: nampak saat diam, hilang saat swipe
  const shadowOpacity = translateX.interpolate({
    inputRange: [0, 80],
    outputRange: [1, 0],
    extrapolate: 'clamp',
  });

  const panResponder = useRef(
    PanResponder.create({
      // Aktifkan hanya jika:
      // - mulai dari pinggir kiri (x < 60px)
      // - gerak horizontal lebih besar dari vertikal
      onMoveShouldSetPanResponder: (_, g) =>
        g.moveX < 60 && g.dx > 6 && Math.abs(g.dy) < Math.abs(g.dx),

      onPanResponderMove: (_, g) => {
        if (g.dx > 0) translateX.setValue(g.dx);
      },

      onPanResponderRelease: (_, g) => {
        const shouldDismiss = g.dx > width * 0.35 || g.vx > 0.5;

        if (shouldDismiss) {
          Animated.timing(translateX, {
            toValue: width,
            duration: 250,
            useNativeDriver: true,
          }).start(() => {
            onSwipeBack?.({ isSwipe: true });
          });
        } else {
          // Snap kembali dengan spring natural
          Animated.spring(translateX, {
            toValue: 0,
            useNativeDriver: true,
            damping: 18,
            stiffness: 200,
            mass: 0.8,
          }).start();
        }
      },

      onPanResponderTerminate: () => {
        Animated.spring(translateX, {
          toValue: 0,
          useNativeDriver: true,
          damping: 18,
          stiffness: 200,
        }).start();
      },
    })
  ).current;

  return (
    <View style={[styles.wrapper, style]}>
      <Animated.View
        style={[styles.screen, { transform: [{ translateX }] }]}
        {...panResponder.panHandlers}
      >
        {/* Shadow tepi kiri diletakkan di luar batas kiri screen agar jatuh ke screen bawahnya */}
        <Animated.View
          style={[styles.shadowEdge, { opacity: shadowOpacity }]}
          pointerEvents="none"
        />
        {children}
      </Animated.View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    flex: 1,
    backgroundColor: 'transparent',
  },
  screen: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  shadowEdge: {
    position: 'absolute',
    left: -20, // di luar batas kiri
    top: 0,
    bottom: 0,
    width: 20,
    backgroundColor: 'transparent',
    shadowColor: '#000',
    shadowOffset: { width: 5, height: 0 },
    shadowOpacity: 0.15,
    shadowRadius: 10,
    elevation: 5,
  },
});
