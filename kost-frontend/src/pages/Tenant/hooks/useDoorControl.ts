// File: src/pages/Tenant/hooks/useDoorControl.ts
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-hot-toast';
import { tenantService } from '../services/tenantService';

export const useTenantDoorControl = () => {
  const queryClient = useQueryClient();

  // Get door status
  const { data: doorStatus, isLoading: isDoorStatusLoading } = useQuery({
    queryKey: ['tenant', 'door-status'],
    queryFn: () => tenantService.getMyRoomDoorStatus(),
    refetchInterval: 30000, // Refresh every 30 seconds
    retry: 1,
    staleTime: 10000, // Consider data stale after 10 seconds
  });

  // Open door mutation
  const openDoorMutation = useMutation({
    mutationFn: (reason?: string) => tenantService.openMyRoomDoor(reason),
    onSuccess: () => {
      toast.success('Pintu berhasil dibuka! 🚪');
      // Refresh door status after opening
      queryClient.invalidateQueries({ queryKey: ['tenant', 'door-status'] });
    },
    onError: (error: unknown) => {
      console.error('Error opening door:', error);
      // Pesan error akan ditangani oleh interceptor global di api.ts
      // const message = error instanceof Error ? error.message : 'Gagal membuka pintu';
      // toast.error(message);
    },
  });

  return {
    doorStatus,
    isDoorStatusLoading,
    openDoor: openDoorMutation.mutate,
    isOpeningDoor: openDoorMutation.isPending,
    doorError: openDoorMutation.error,
  };
};

export default useTenantDoorControl;