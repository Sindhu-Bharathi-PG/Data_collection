-- Hospital Profiles Table for Neon PostgreSQL
-- Run this SQL in your Neon SQL Editor

CREATE TABLE IF NOT EXISTS hospital_profiles (
    id SERIAL PRIMARY KEY,
    
    -- Basic Information
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL CHECK (type IN ('Government', 'Private')),
    establishment_year INTEGER,
    accreditations JSONB DEFAULT '[]',
    beds INTEGER,
    patient_count JSONB DEFAULT '{}',
    
    -- Location & Contact (stored as JSON)
    location JSONB NOT NULL,
    contact JSONB DEFAULT '{}',
    
    -- Content & Descriptions
    description JSONB DEFAULT '{}',
    
    -- Clinical Capabilities
    departments JSONB DEFAULT '[]',
    specialties JSONB DEFAULT '[]',
    equipment JSONB DEFAULT '[]',
    facilities JSONB DEFAULT '[]',
    
    -- Dynamic Sections
    doctors JSONB DEFAULT '[]',
    treatments JSONB DEFAULT '[]',
    packages JSONB DEFAULT '[]',
    photos JSONB DEFAULT '[]',
    
    -- Metadata
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create index for faster searches
CREATE INDEX IF NOT EXISTS idx_hospital_name ON hospital_profiles(name);
CREATE INDEX IF NOT EXISTS idx_hospital_type ON hospital_profiles(type);
CREATE INDEX IF NOT EXISTS idx_hospital_status ON hospital_profiles(status);
CREATE INDEX IF NOT EXISTS idx_hospital_city ON hospital_profiles USING GIN ((location->'city'));

-- Add a trigger to update the updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_hospital_profiles_updated_at
    BEFORE UPDATE ON hospital_profiles
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();
