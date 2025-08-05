import React from 'react';
import { MapPin } from 'lucide-react';

interface HeroSectionProps {
  onBookingClick: () => void;
  className?: string;
}

export const HeroSection: React.FC<HeroSectionProps> = ({
  onBookingClick,
  className = ''
}) => {
  return (
    <section 
      id="hero" 
      className={`relative min-h-screen bg-gradient-to-br from-blue-50 to-blue-100 text-blue-900 flex items-center ${className}`}
      style={{ marginTop: '64px' }}
    >
      <div className="absolute inset-0 bg-black/20"></div>
      
      <div className="container mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10 max-w-4xl">
        <div className="space-y-8">
          <div className="space-y-4">
            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight">
              POTUNA KOS
            </h1>
            <p className="text-lg sm:text-xl text-blue-700 max-w-2xl mx-auto">
              Kos nyaman dan strategis di Jagakarsa untuk mahasiswa dan pekerja
            </p>
          </div>
          
          <div className="flex justify-center">
            <div className="flex items-center gap-2 bg-teal-400/20 px-4 py-2 rounded-full backdrop-blur-sm">
              <MapPin size={16} className="text-teal-600" />
              <span>Jagakarsa, Jakarta Selatan</span>
            </div>
          </div>
          
          <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <button 
              onClick={onBookingClick}
              className="w-full sm:w-auto bg-orange-400 text-white px-8 py-3 rounded-lg font-semibold hover:bg-orange-500 transition-colors duration-200 shadow-sm"
            >
              Booking Sekarang
            </button>
          </div>
        </div>
      </div>
    </section>
  );
};