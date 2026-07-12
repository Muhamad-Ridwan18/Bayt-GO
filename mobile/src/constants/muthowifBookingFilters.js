import {
  CheckCircle2,
  Clock3,
  Flag,
  Footprints,
  Layers,
  XCircle,
} from 'lucide-react-native';

export const MUTHOWIF_BOOKING_FILTERS = [
  { value: 'all', label: 'Semua', icon: Layers },
  { value: 'pending', label: 'Menunggu', icon: Clock3 },
  { value: 'confirmed', label: 'Dikonfirmasi', icon: CheckCircle2 },
  { value: 'in_progress', label: 'Berlangsung', icon: Footprints },
  { value: 'completed', label: 'Selesai', icon: Flag },
  { value: 'cancelled', label: 'Dibatalkan', icon: XCircle },
];
