package com.example.englishvocab.repository;

import com.example.englishvocab.entity.PartOfSpeech;
import org.springframework.data.jpa.repository.JpaRepository;

public interface PartOfSpeechRepository extends JpaRepository<PartOfSpeech, Integer> {
}
