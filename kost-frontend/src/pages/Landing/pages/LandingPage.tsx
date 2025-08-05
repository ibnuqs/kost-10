import React from 'react';
import { LandingLayout } from '../components';
// Fix: Import modals directly from modals directory
import { 
  BookingModal,
  GalleryModal,
  ContactModal
} from '../components/modals';
import {
  HeroSection,
  FeaturesSection,
  PricingSection,
  TestimonialsSection,
  CTASection,
  FooterSection
} from '../components/sections';
import { useModalState } from '../hooks';
import { RoomType, BookingFormData, ContactFormData } from '../types';
import { analyticsService } from '../services';

interface LandingPageProps {
  onLoginClick?: () => void;
}

export const LandingPage: React.FC<LandingPageProps> = ({
  onLoginClick
}) => {
  const { 
    modals, 
    selectedRoomType,
    galleryImages,
    galleryStartIndex,
    galleryCategory,
    openModal, 
    closeModal 
  } = useModalState();

  // Modal handlers
  const handleBookingClick = (roomType?: RoomType) => {
    if (roomType) {
      openModal('booking', { roomType: roomType.id });
      analyticsService.trackBookingInquiry(roomType.name, 'pricing_section');
    } else {
      openModal('booking');
      analyticsService.trackBookingInquiry('general', 'hero_section');
    }
  };

  const handleViewRoomDetails = (roomType: RoomType) => {
    openModal('gallery', { 
      images: roomType.images,
      category: roomType.name 
    });
    analyticsService.trackImageView('room', roomType.name);
  };

  const handleContactClick = () => {
    openModal('contact');
    analyticsService.trackModalOpen('contact');
  };

  // Modal submit handlers
  const handleBookingSubmit = (data: BookingFormData) => {
    console.log('Booking submitted:', data);
    // Additional booking logic can be added here
  };

  const handleContactSubmit = (data: ContactFormData) => {
    console.log('Contact submitted:', data);
    // Additional contact logic can be added here
  };

  return (
    <LandingLayout 
      onBookingClick={() => handleBookingClick()}
      onLoginClick={onLoginClick}
    >
      {/* Hero Section */}
      <HeroSection
        onBookingClick={() => {
          handleBookingClick();
          analyticsService.trackButtonClick('hero_booking', 'hero');
        }}
      />

      {/* Features Section */}
      <FeaturesSection />

      {/* Pricing Section */}
      <PricingSection
        onBookNow={(roomType) => {
          handleBookingClick(roomType);
          analyticsService.trackButtonClick('pricing_book_now', 'pricing');
        }}
        onViewDetails={(roomType) => {
          handleViewRoomDetails(roomType);
          analyticsService.trackButtonClick('pricing_view_details', 'pricing');
        }}
        onContactClick={() => {
          handleContactClick();
          analyticsService.trackButtonClick('pricing_contact', 'cta');
        }}
      />

      {/* Testimonials Section */}
      <TestimonialsSection />

      {/* CTA Section */}
      <CTASection
        onBookingClick={() => {
          handleBookingClick();
          analyticsService.trackButtonClick('cta_booking', 'cta');
        }}
        onContactClick={() => {
          handleContactClick();
          analyticsService.trackButtonClick('cta_contact', 'cta');
        }}
      />

      {/* Footer Section */}
      <FooterSection
        onBookingClick={() => {
          handleBookingClick();
          analyticsService.trackButtonClick('footer_booking', 'footer');
        }}
      />

      {/* Modal Components */}
      <BookingModal
        isOpen={modals.booking}
        onClose={() => closeModal('booking')}
        roomType={selectedRoomType || undefined}
        onSubmitBooking={handleBookingSubmit}
      />

      <GalleryModal
        isOpen={modals.gallery}
        onClose={() => closeModal('gallery')}
        images={galleryImages}
        startIndex={galleryStartIndex}
        category={galleryCategory}
      />

      <ContactModal
        isOpen={modals.contact}
        onClose={() => closeModal('contact')}
        onSubmitContact={handleContactSubmit}
      />
    </LandingLayout>
  );
};