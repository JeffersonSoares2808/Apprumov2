-- Add cover_position column to vendors table for Facebook-style cover image positioning
ALTER TABLE vendors ADD COLUMN cover_position VARCHAR(20) NOT NULL DEFAULT 'center' AFTER cover_image;
