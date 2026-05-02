import React from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '@/Layouts/AppLayout';

const Welcome = ({ user }: { user: any }) => {
    return (
        <div className="flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <Head title="Ana Sayfa" />
            
            <div className="max-w-md w-full bg-white rounded-2xl shadow-lg p-8 text-center">
                <div className="mb-6 flex justify-center">
                    <div className="bg-blue-100 p-4 rounded-full">
                        <svg className="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                
                <h1 className="text-3xl font-bold text-gray-900 mb-2">Hoş Geldiniz!</h1>
                <p className="text-gray-600 mb-8">
                    Inertia.js + React ile Denti Management sistemine giriş yaptınız.
                </p>
                
                <div className="bg-gray-50 rounded-lg p-4 text-sm text-gray-700 border border-gray-100">
                    Kullanıcı: <span className="text-blue-600 font-semibold">{user?.name || 'Test Kullanıcısı'}</span>
                </div>
            </div>
        </div>
    );
};

Welcome.layout = (page: React.ReactNode) => <AppLayout children={page} />

export default Welcome;
